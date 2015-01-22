<?php

namespace RedMatrix\RedDAV;

use Sabre\DAV;

/**
 * @brief Provides a DAV frontend for the webbrowser.
 *
 * RedBrowser is a SabreDAV server-plugin to provide a view to the DAV storage
 * for the webbrowser.
 *
 * @extends \Sabre\DAV\Browser\Plugin
 *
 * @link http://github.com/friendica/red
 * @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
 */
class RedBrowser extends DAV\Browser\Plugin {

	/**
	 * @see set_writeable()
	 * @see \Sabre\DAV\Auth\Backend\BackendInterface
	 * @var RedBasicAuth
	 */
	private $auth;

	/**
	 * @brief Constructor for RedBrowser class.
	 *
	 * $enablePost will be activated through set_writeable() in a later stage.
	 * At the moment the write_storage permission is only valid for the whole
	 * folder. No file specific permissions yet.
	 * @todo disable enablePost by default and only activate if permissions
	 * grant edit rights.
	 *
	 * Disable assets with $enableAssets = false. Should get some thumbnail views
	 * anyway.
	 *
	 * @param RedBasicAuth &$auth
	 */
	public function __construct(&$auth) {
		$this->auth = $auth;
		parent::__construct(true, false);
	}

	/**
	 * The DAV browser is instantiated after the auth module and directory classes
	 * but before we know the current directory and who the owner and observer
	 * are. So we add a pointer to the browser into the auth module and vice versa.
	 * Then when we've figured out what directory is actually being accessed, we
	 * call the following function to decide whether or not to show web elements
	 * which include writeable objects.
	 *
	 * @fixme It only disable/enable the visible parts. Not the POST handler
	 * which handels the actual requests when uploading files or creating folders.
	 *
	 * @todo Maybe this whole way of doing this can be solved with some
	 * $server->subscribeEvent().
	 */
	public function set_writeable() {
		if (! $this->auth->owner_id) {
			$this->enablePost = false;
		}

		if (! perm_is_allowed($this->auth->owner_id, get_observer_hash(), 'write_storage')) {
			$this->enablePost = false;
		} else {
			$this->enablePost = true;
		}
	}

	/**
	 * @brief Creates the directory listing for the given path.
	 *
	 * @param string $path which should be displayed
	 */
	public function generateDirectoryIndex($path) {
		// (owner_id = channel_id) is visitor owner of this directory?
		$is_owner = ((local_user() && $this->auth->owner_id == local_user()) ? true : false);

		if ($this->auth->getTimezone())
			date_default_timezone_set($this->auth->getTimezone());

		require_once('include/conversation.php');

		if ($this->auth->owner_nick) {
			$html = profile_tabs(get_app(), (($is_owner) ? true : false), $this->auth->owner_nick);
		}

		$files = $this->server->getPropertiesForPath($path, array(
			'{DAV:}displayname',
			'{DAV:}resourcetype',
			'{DAV:}getcontenttype',
			'{DAV:}getcontentlength',
			'{DAV:}getlastmodified',
			), 1);

		$parent = $this->server->tree->getNodeForPath($path);

		$parentpath = array();
		// only show parent if not leaving /cloud/; TODO how to improve this? 
		if ($path && $path != "cloud") {
			list($parentUri) = DAV\URLUtil::splitPath($path);
			$fullPath = DAV\URLUtil::encodePath($this->server->getBaseUri() . $parentUri);

			$parentpath['icon'] = $this->enableAssets ? '<a href="' . $fullPath . '"><img src="' . $this->getAssetUrl('icons/parent' . $this->iconExtension) . '" width="24" alt="' . t('parent') . '"></a>' : '';
			$parentpath['path'] = $fullPath;
		}

		$f = array();
		foreach ($files as $file) {
			$ft = array();
			$type = null;

			// This is the current directory, we can skip it
			if (rtrim($file['href'],'/') == $path) continue;

			list(, $name) = DAV\URLUtil::splitPath($file['href']);

			if (isset($file[200]['{DAV:}resourcetype'])) {
				$type = $file[200]['{DAV:}resourcetype']->getValue();

				// resourcetype can have multiple values
				if (!is_array($type)) $type = array($type);

				foreach ($type as $k=>$v) {
					// Some name mapping is preferred
					switch ($v) {
						case '{DAV:}collection' :
							$type[$k] = t('Collection');
							break;
						case '{DAV:}principal' :
							$type[$k] = t('Principal');
							break;
						case '{urn:ietf:params:xml:ns:carddav}addressbook' :
							$type[$k] = t('Addressbook');
							break;
						case '{urn:ietf:params:xml:ns:caldav}calendar' :
							$type[$k] = t('Calendar');
							break;
						case '{urn:ietf:params:xml:ns:caldav}schedule-inbox' :
							$type[$k] = t('Schedule Inbox');
							break;
						case '{urn:ietf:params:xml:ns:caldav}schedule-outbox' :
							$type[$k] = t('Schedule Outbox');
							break;
						case '{http://calendarserver.org/ns/}calendar-proxy-read' :
							$type[$k] = 'Proxy-Read';
							break;
						case '{http://calendarserver.org/ns/}calendar-proxy-write' :
							$type[$k] = 'Proxy-Write';
							break;
					}
				}
				$type = implode(', ', $type);
			}

			// If no resourcetype was found, we attempt to use
			// the contenttype property
			if (!$type && isset($file[200]['{DAV:}getcontenttype'])) {
				$type = $file[200]['{DAV:}getcontenttype'];
			}
			if (!$type) $type = t('Unknown');

			$size = isset($file[200]['{DAV:}getcontentlength']) ? (int)$file[200]['{DAV:}getcontentlength'] : '';
			$lastmodified = ((isset($file[200]['{DAV:}getlastmodified'])) ? $file[200]['{DAV:}getlastmodified']->getTime()->format('Y-m-d H:i:s') : '');

			$fullPath = DAV\URLUtil::encodePath('/' . trim($this->server->getBaseUri() . ($path ? $path . '/' : '') . $name, '/'));

			$displayName = isset($file[200]['{DAV:}displayname']) ? $file[200]['{DAV:}displayname'] : $name;

			$displayName = $this->escapeHTML($displayName);
			$type = $this->escapeHTML($type);

			$icon = '';

			if ($this->enableAssets) {
				$node = $this->server->tree->getNodeForPath(($path ? $path . '/' : '') . $name);
				foreach (array_reverse($this->iconMap) as $class=>$iconName) {
					if ($node instanceof $class) {
						$icon = '<a href="' . $fullPath . '"><img src="' . $this->getAssetUrl($iconName . $this->iconExtension) . '" alt="" width="24"></a>';
						break;
					}
				}
			}

			$parentHash = '';
			$owner = $this->auth->owner_id;
			$splitPath = split('/', $fullPath);
			if (count($splitPath) > 3) {
				for ($i = 3; $i < count($splitPath); $i++) {
					$attachName = urldecode($splitPath[$i]);
					$attachHash = $this->findAttachHash($owner, $parentHash, $attachName);
					$parentHash = $attachHash;
				}
			}

			$attachIcon = ""; // "<a href=\"attach/".$attachHash."\" title=\"".$displayName."\"><i class=\"icon-download\"></i></a>";

			// put the array for this file together
			$ft['attachId'] = $this->findAttachIdByHash($attachHash);
			$ft['fileStorageUrl'] = substr($fullPath, 0, strpos($fullPath, "cloud/")) . "filestorage/" . $this->auth->getCurrentUser();
			$ft['icon'] = $icon;
			$ft['attachIcon'] = (($size) ? $attachIcon : '');
			// @todo Should this be an item value, not a global one?
			$ft['is_owner'] = $is_owner;
			$ft['fullPath'] = $fullPath;
			$ft['displayName'] = $displayName;
			$ft['type'] = $type;
			$ft['size'] = $size;
			$ft['sizeFormatted'] = $this->userReadableSize($size);
			$ft['lastmodified'] = (($lastmodified) ? datetime_convert('UTC', date_default_timezone_get(), $lastmodified) : '');
			$ft['iconFromType'] = $this->getIconFromType($type);

			$f[] = $ft;
		}

		// Storage and quota for the account (all channels of the owner of this directory)!
		$limit = service_class_fetch($owner, 'attach_upload_limit');
		$r = q("SELECT SUM(filesize) AS total FROM attach WHERE aid = %d",
			intval($this->auth->channel_account_id)
		);
		$used = $r[0]['total'];
		if ($used) {
			$quotaDesc = t('%1$s used');
			$quotaDesc = sprintf($quotaDesc,
				$this->userReadableSize($used));
		}
		if ($limit && $used) {
			$quotaDesc = t('%1$s used of %2$s (%3$s&#37;)');
			$quotaDesc = sprintf($quotaDesc,
				$this->userReadableSize($used),
				$this->userReadableSize($limit),
				round($used / $limit, 1));
		}

		// prepare quota for template
		$quota = array();
		$quota['used'] = $used;
		$quota['limit'] = $limit;
		$quota['desc'] = $quotaDesc;

		$output = '';
		if ($this->enablePost) {
			$this->server->broadcastEvent('onHTMLActionsPanel', array($parent, &$output));
		}

		$html .= replace_macros(get_markup_template('cloud_header.tpl'), array(
				'$header' => t('Files') . ": " . $this->escapeHTML($path) . "/",
				'$quota' => $quota,
				'$total' => t('Total'),
				'$actionspanel' => $output,
				'$shared' => t('Shared'),
				'$create' => t('Create'),
				'upload' => t('Upload')
			));

		$html .= replace_macros(get_markup_template('cloud_directory.tpl'), array(
				'$parentpath' => $parentpath,
				'$entries' => $f,
				'$name' => t('Name'),
				'$type' => t('Type'),
				'$size' => t('Size'),
				'$lastmod' => t('Last Modified'),
				'$parent' => t('parent'),
				'$edit' => t('Edit'),
				'$delete' => t('Delete'),
				'$nick' => $this->auth->getCurrentUser()
			));

		get_app()->page['content'] = $html;
		load_pdl(get_app());

		$theme_info_file = "view/theme/" . current_theme() . "/php/theme.php";
		if (file_exists($theme_info_file)){
			require_once($theme_info_file);
			if (function_exists(str_replace('-', '_', current_theme()) . '_init')) {
				$func = str_replace('-', '_', current_theme()) . '_init';
				$func(get_app());
			}
		}
		construct_page(get_app());
	}

	/**
	 * @brief Returns a human readable formatted string for filesizes.
	 *
	 * Don't we need such a functionality in other places, too?
	 *
	 * @param int $size filesize in bytes
	 * @return string
	 */
	function userReadableSize($size) {
		$ret = "";
		if (is_numeric($size)) {
			$incr = 0;
			$k = 1024;
			$unit = array('bytes', 'KB', 'MB', 'GB', 'TB', 'PB');
			while (($size / $k) >= 1){
				$incr++;
				$size = round($size / $k, 2);
			}
			$ret = $size . " " . $unit[$incr];
		}
		return $ret;
	}

	/**
	 * @brief Creates a form to add new folders and upload files.
	 *
	 * @param \Sabre\DAV\INode $node
	 * @param string &$output
	 */
	public function htmlActionsPanel(DAV\INode $node, &$output) {
		if (! $node instanceof DAV\ICollection)
			return;

		// We also know fairly certain that if an object is a non-extended
		// SimpleCollection, we won't need to show the panel either.
		if (get_class($node) === 'Sabre\\DAV\\SimpleCollection')
			return;

		$output .= replace_macros(get_markup_template('cloud_actionspanel.tpl'), array(
				'$folder_header' => t('Create new folder'),
				'$folder_submit' => t('Create'),
				'$upload_header' => t('Upload file'),
				'$upload_submit' => t('Upload')
			));
	}

	/**
	 * This method takes a path/name of an asset and turns it into url
	 * suiteable for http access.
	 *
	 * @param string $assetName
	 * @return string
	 */
	protected function getAssetUrl($assetName) {
		return z_root() . '/cloud/?sabreAction=asset&assetName=' . urlencode($assetName);
	}

	/**
	 * @brief returns icon name for use with e.g. font-awesome based on mime-type
	 *
	 * @param string $type
	 * @return string
	 */
	protected function getIconFromType($type) {
		$iconMap = array(
					//Folder
					t('Collection') => 'icon-folder-close',

					//Common file
					'application/octet-stream' => 'icon-file-alt',

					//Text
					'text/plain' => 'icon-file-text-alt',
					'application/msword' => 'icon-file-text-alt',
					'application/pdf' => 'icon-file-text-alt',
					'application/vnd.oasis.opendocument.text' => 'icon-file-text-alt',
					'application/epub+zip' => 'icon-book',

					//Spreadsheet
					'application/vnd.oasis.opendocument.spreadsheet' => 'icon-table',
					'application/vnd.ms-excel' => 'icon-table',

					//Image
					'image/jpeg' => 'icon-picture',
					'image/png' => 'icon-picture',
					'image/gif' => 'icon-picture',
					'image/svg+xml' => 'icon-picture',

					//Archive
					'application/zip' => 'icon-archive',
					'application/x-rar-compressed' => 'icon-archive',

					//Audio
					'audio/mpeg' => 'icon-music',
					'audio/wav' => 'icon-music',
					'application/ogg' => 'icon-music',
					'audio/ogg' => 'icon-music',
					'audio/webm' => 'icon-music',
					'audio/mp4' => 'icon-music',

					//Video
					'video/quicktime' => 'icon-film',
					'video/webm' => 'icon-film',
					'video/mp4' => 'icon-film'
				);

		$iconFromType = 'icon-file-alt';

		if (array_key_exists($type, $iconMap))
			{
			    $iconFromType = $iconMap[$type];
			}

		return $iconFromType;
	}

	/**
	 * @brief Return the hash of an attachment.
	 *
	 * Given the owner, the parent folder and and attach name get the attachment
	 * hash.
	 * 
	 * @param int $owner
	 *  The owner_id
	 * @param string $hash
	 *  The parent's folder hash
	 * @param string $attachName
	 *  The name of the attachment
	 * @return string
	 */
	protected function findAttachHash($owner, $parentHash, $attachName) {
		$r = q("SELECT hash FROM attach WHERE uid = %d AND folder = '%s' AND filename = '%s' ORDER BY edited DESC LIMIT 1",
			intval($owner),
			dbesc($parentHash),
			dbesc($attachName)
		);
		$hash = "";
		if ($r) {
			foreach ($r as $rr) {
				$hash = $rr['hash'];
			}
		}
		return $hash;
	}

	/**
	 * @brief Returns an attachment's id for a given hash.
	 *
	 * This id is used to access the attachment in filestorage/
	 *
	 * @param string $attachHash
	 *  The hash of an attachment
	 * @return string
	 */
	protected function findAttachIdByHash($attachHash) {
		$r = q("SELECT id FROM attach WHERE hash = '%s' ORDER BY edited DESC LIMIT 1",
			dbesc($attachHash)
		);
		$id = "";
		if ($r) {
			foreach ($r as $rr) {
				$id = $rr['id'];
			}
		}
		return $id;
	}
}
