CREATE TABLE "abook" (
  "abook_id" serial  NOT NULL,
  "abook_account" bigint  NOT NULL,
  "abook_channel" bigint  NOT NULL,
  "abook_xchan" text NOT NULL DEFAULT '',
  "abook_my_perms" bigint NOT NULL DEFAULT '0',
  "abook_their_perms" bigint NOT NULL DEFAULT '0',
  "abook_closeness" numeric(3)  NOT NULL DEFAULT '99',
  "abook_rating" bigint NOT NULL DEFAULT '0',
  "abook_rating_text" TEXT NOT NULL DEFAULT '',
  "abook_created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "abook_updated" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "abook_connected" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "abook_dob" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "abook_flags" bigint NOT NULL DEFAULT '0',
  "abook_profile" char(64) NOT NULL DEFAULT '',
  PRIMARY KEY ("abook_id")
);
  create index  "abook_account" on abook ("abook_account");
  create index  "abook_channel" on abook  ("abook_channel");
  create index  "abook_xchan"  on abook ("abook_xchan");
  create index  "abook_my_perms" on abook  ("abook_my_perms");
  create index  "abook_their_perms" on abook  ("abook_their_perms");
  create index  "abook_closeness" on abook  ("abook_closeness");
  create index  "abook_created"  on abook ("abook_created");
  create index  "abook_updated"  on abook ("abook_updated");
  create index  "abook_flags"  on abook ("abook_flags");
  create index  "abook_profile" on abook  ("abook_profile");
  create index  "abook_dob" on abook  ("abook_dob");
  create index  "abook_connected" on abook  ("abook_connected");
  create index  "abook_rating" on abook  ("abook_rating");

CREATE TABLE "account" (
  "account_id" serial  NOT NULL,
  "account_parent" bigint  NOT NULL DEFAULT '0',
  "account_default_channel" bigint  NOT NULL DEFAULT '0',
  "account_salt" char(32) NOT NULL DEFAULT '',
  "account_password" text NOT NULL DEFAULT '',
  "account_email" text NOT NULL DEFAULT '',
  "account_external" text NOT NULL DEFAULT '',
  "account_language" varchar(16) NOT NULL DEFAULT 'en',
  "account_created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "account_lastlog" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "account_flags" bigint  NOT NULL DEFAULT '0',
  "account_roles" bigint  NOT NULL DEFAULT '0',
  "account_reset" text NOT NULL DEFAULT '',
  "account_expires" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "account_expire_notified" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "account_service_class" varchar(32) NOT NULL DEFAULT '',
  "account_level" bigint  NOT NULL DEFAULT '0',
  "account_password_changed" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("account_id")
);
create index "account_email" on account ("account_email");
create index "account_service_class" on account ("account_service_class");
create index "account_parent" on account ("account_parent");
create index "account_flags"  on account ("account_flags");
create index "account_roles"  on account ("account_roles");
create index "account_lastlog"  on account ("account_lastlog");
create index "account_expires"  on account ("account_expires");
create index "account_default_channel"  on account ("account_default_channel");
create index "account_external"  on account ("account_external");
create index "account_level"  on account ("account_level");
create index "account_password_changed"  on account ("account_password_changed");
CREATE TABLE "addon" (
  "id" serial NOT NULL,
  "name" text NOT NULL,
  "version" text NOT NULL DEFAULT '0',
  "installed" numeric(1) NOT NULL DEFAULT '0',
  "hidden" numeric(1) NOT NULL DEFAULT '0',
  "timestamp" numeric(20) NOT NULL DEFAULT '0',
  "plugin_admin" numeric(1) NOT NULL DEFAULT '0',
  PRIMARY KEY ("id")
);
create index "addon_hidden_idx" on addon ("hidden");
create index "addon_name_idx" on addon ("name");
create index "addon_installed_idx" on addon ("installed");
CREATE TABLE "app" (
  "id" serial NOT NULL,
  "app_id" text NOT NULL DEFAULT '',
  "app_sig" text NOT NULL DEFAULT '',
  "app_author" text NOT NULL DEFAULT '',
  "app_name" text NOT NULL DEFAULT '',
  "app_desc" text NOT NULL,
  "app_url" text NOT NULL DEFAULT '',
  "app_photo" text NOT NULL DEFAULT '',
  "app_version" text NOT NULL DEFAULT '',
  "app_channel" bigint NOT NULL DEFAULT '0',
  "app_addr" text NOT NULL DEFAULT '',
  "app_price" text NOT NULL DEFAULT '',
  "app_page" text NOT NULL DEFAULT '',
  "app_requires" text NOT NULL DEFAULT '',
  PRIMARY KEY ("id")
);
create index "app_id" on app ("app_id");
create index "app_name" on app ("app_name");
create index "app_url" on app ("app_url");
create index "app_photo" on app ("app_photo");
create index "app_version" on app ("app_version");
create index "app_channel" on app ("app_channel");
create index "app_price" on app ("app_price");
CREATE TABLE "attach" (
  "id" serial  NOT NULL,
  "aid" bigint  NOT NULL DEFAULT '0',
  "uid" bigint  NOT NULL DEFAULT '0',
  "hash" varchar(64) NOT NULL DEFAULT '',
  "creator" varchar(128) NOT NULL DEFAULT '',
  "filename" text NOT NULL DEFAULT '',
  "filetype" varchar(64) NOT NULL DEFAULT '',
  "filesize" bigint  NOT NULL DEFAULT '0',
  "revision" bigint  NOT NULL DEFAULT '0',
  "folder" varchar(64) NOT NULL DEFAULT '',
  "flags" bigint  NOT NULL DEFAULT '0',
  "data" bytea NOT NULL,
  "created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "edited" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  PRIMARY KEY ("id")

);
create index "attach_aid_idx" on attach ("aid");
create index "attach_uid_idx" on attach ("uid");
create index "attach_hash_idx" on attach ("hash");
create index "attach_filename_idx" on attach ("filename");
create index "attach_filetype_idx" on attach ("filetype");
create index "attach_filesize_idx" on attach ("filesize");
create index "attach_created_idx" on attach ("created");
create index "attach_edited_idx" on attach ("edited");
create index "attach_revision_idx" on attach ("revision");
create index "attach_folder_idx" on attach ("folder");
create index "attach_flags_idx" on attach ("flags");
create index "attach_creator_idx" on attach ("creator");
CREATE TABLE "auth_codes" (
  "id" varchar(40) NOT NULL,
  "client_id" varchar(20) NOT NULL,
  "redirect_uri" varchar(200) NOT NULL,
  "expires" bigint NOT NULL,
  "scope" varchar(250) NOT NULL,
  PRIMARY KEY ("id")
);
CREATE TABLE "cache" (
  "k" text NOT NULL,
  "v" text NOT NULL,
  "updated" timestamp NOT NULL,
  PRIMARY KEY ("k")
);
CREATE TABLE "channel" (
  "channel_id" serial  NOT NULL,
  "channel_account_id" bigint  NOT NULL DEFAULT '0',
  "channel_primary" numeric(1)  NOT NULL DEFAULT '0',
  "channel_name" text NOT NULL DEFAULT '',
  "channel_address" text NOT NULL DEFAULT '',
  "channel_guid" text NOT NULL DEFAULT '',
  "channel_guid_sig" text NOT NULL,
  "channel_hash" text NOT NULL DEFAULT '',
  "channel_timezone" varchar(128) NOT NULL DEFAULT 'UTC',
  "channel_location" text NOT NULL DEFAULT '',
  "channel_theme" text NOT NULL DEFAULT '',
  "channel_startpage" text NOT NULL DEFAULT '',
  "channel_pubkey" text NOT NULL,
  "channel_prvkey" text NOT NULL,
  "channel_notifyflags" bigint  NOT NULL DEFAULT '65535',
  "channel_pageflags" bigint  NOT NULL DEFAULT '0',
  "channel_dirdate" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "channel_deleted" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "channel_max_anon_mail" bigint  NOT NULL DEFAULT '10',
  "channel_max_friend_req" bigint  NOT NULL DEFAULT '10',
  "channel_expire_days" bigint NOT NULL DEFAULT '0',
  "channel_passwd_reset" text NOT NULL DEFAULT '',
  "channel_default_group" text NOT NULL DEFAULT '',
  "channel_allow_cid" text ,
  "channel_allow_gid" text ,
  "channel_deny_cid" text ,
  "channel_deny_gid" text ,
  "channel_r_stream" bigint  NOT NULL DEFAULT '128',
  "channel_r_profile" bigint  NOT NULL DEFAULT '128',
  "channel_r_photos" bigint  NOT NULL DEFAULT '128',
  "channel_r_abook" bigint  NOT NULL DEFAULT '128',
  "channel_w_stream" bigint  NOT NULL DEFAULT '128',
  "channel_w_wall" bigint  NOT NULL DEFAULT '128',
  "channel_w_tagwall" bigint  NOT NULL DEFAULT '128',
  "channel_w_comment" bigint  NOT NULL DEFAULT '128',
  "channel_w_mail" bigint  NOT NULL DEFAULT '128',
  "channel_w_photos" bigint  NOT NULL DEFAULT '128',
  "channel_w_chat" bigint  NOT NULL DEFAULT '128',
  "channel_a_delegate" bigint  NOT NULL DEFAULT '0',
  "channel_r_storage" bigint  NOT NULL DEFAULT '128',
  "channel_w_storage" bigint  NOT NULL DEFAULT '128',
  "channel_r_pages" bigint  NOT NULL DEFAULT '128',
  "channel_w_pages" bigint  NOT NULL DEFAULT '128',
  "channel_a_republish" bigint  NOT NULL DEFAULT '128',
  "channel_w_like" bigint  NOT NULL DEFAULT '128',
  PRIMARY KEY ("channel_id"),
  UNIQUE ("channel_address")
);
create index "channel_account_id" on channel ("channel_account_id");
create index "channel_primary" on channel ("channel_primary");
create index "channel_name" on channel ("channel_name");
create index "channel_timezone" on channel ("channel_timezone");
create index "channel_location" on channel ("channel_location");
create index "channel_theme" on channel ("channel_theme");
create index "channel_notifyflags" on channel ("channel_notifyflags");
create index "channel_pageflags" on channel ("channel_pageflags");
create index "channel_max_anon_mail" on channel ("channel_max_anon_mail");
create index "channel_max_friend_req" on channel ("channel_max_friend_req");
create index "channel_default_gid" on channel ("channel_default_group");
create index "channel_r_stream" on channel ("channel_r_stream");
create index "channel_r_profile" on channel ("channel_r_profile");
create index "channel_r_photos" on channel ("channel_r_photos");
create index "channel_r_abook" on channel ("channel_r_abook");
create index "channel_w_stream" on channel ("channel_w_stream");
create index "channel_w_wall" on channel ("channel_w_wall");
create index "channel_w_tagwall" on channel ("channel_w_tagwall");
create index "channel_w_comment" on channel ("channel_w_comment");
create index "channel_w_mail" on channel ("channel_w_mail");
create index "channel_w_photos" on channel ("channel_w_photos");
create index "channel_w_chat" on channel ("channel_w_chat");
create index "channel_guid" on channel ("channel_guid");
create index "channel_hash" on channel ("channel_hash");
create index "channel_expire_days" on channel ("channel_expire_days");
create index "channel_a_delegate" on channel ("channel_a_delegate");
create index "channel_r_storage" on channel ("channel_r_storage");
create index "channel_w_storage" on channel ("channel_w_storage");
create index "channel_r_pages" on channel ("channel_r_pages");
create index "channel_w_pages" on channel ("channel_w_pages");
create index "channel_deleted" on channel ("channel_deleted");
create index "channel_a_republish" on channel ("channel_a_republish");
create index "channel_w_like" on channel ("channel_w_like");
create index "channel_dirdate" on channel ("channel_dirdate");
CREATE TABLE "chat" (
  "chat_id" serial  NOT NULL,
  "chat_room" bigint  NOT NULL DEFAULT '0',
  "chat_xchan" text NOT NULL DEFAULT '',
  "chat_text" text NOT NULL,
  "created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("chat_id")
);
create index "chat_room_idx" on chat ("chat_room");
create index "chat_xchan_idx" on chat ("chat_xchan");
create index "chat_created_idx" on chat ("created");
CREATE TABLE "chatpresence" (
  "cp_id" serial  NOT NULL,
  "cp_room" bigint  NOT NULL DEFAULT '0',
  "cp_xchan" text NOT NULL DEFAULT '',
  "cp_last" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "cp_status" text NOT NULL,
  "cp_client" char(128) NOT NULL DEFAULT '',
  PRIMARY KEY ("cp_id")
);
create index "cp_room" on chatpresence ("cp_room");
create index "cp_xchan" on chatpresence  ("cp_xchan");
create index "cp_last" on chatpresence ("cp_last");
create index "cp_status" on chatpresence ("cp_status");

CREATE TABLE "chatroom" (
  "cr_id" serial  NOT NULL,
  "cr_aid" bigint  NOT NULL DEFAULT '0',
  "cr_uid" bigint  NOT NULL DEFAULT '0',
  "cr_name" text NOT NULL DEFAULT '',
  "cr_created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "cr_edited" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "cr_expire" bigint  NOT NULL DEFAULT '0',
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  PRIMARY KEY ("cr_id")
);
create index "cr_aid" on chatroom ("cr_aid");
create index "cr_uid" on chatroom ("cr_uid");
create index "cr_name" on chatroom ("cr_name");
create index "cr_created" on chatroom ("cr_created");
create index "cr_edited" on chatroom ("cr_edited");
create index "cr_expire" on chatroom ("cr_expire");
CREATE TABLE "clients" (
  "client_id" varchar(20) NOT NULL,
  "pw" varchar(20) NOT NULL,
  "redirect_uri" varchar(200) NOT NULL,
  "name" text,
  "icon" text,
  "uid" bigint NOT NULL DEFAULT '0',
  PRIMARY KEY ("client_id")
);
CREATE TABLE "config" (
  "id" serial  NOT NULL,
  "cat" text  NOT NULL,
  "k" text  NOT NULL,
  "v" text NOT NULL,
  PRIMARY KEY ("id"),
  UNIQUE ("cat","k")
);
CREATE TABLE "conv" (
  "id" serial  NOT NULL,
  "guid" text NOT NULL,
  "recips" text NOT NULL,
  "uid" bigint NOT NULL,
  "creator" text NOT NULL,
  "created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "updated" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "subject" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "conv_created_idx" on conv ("created");
create index "conv_updated_idx" on conv ("updated");

CREATE TABLE "event" (
  "id" serial NOT NULL,
  "aid" bigint  NOT NULL DEFAULT '0',
  "uid" bigint NOT NULL,
  "event_xchan" text NOT NULL DEFAULT '',
  "event_hash" text NOT NULL DEFAULT '',
  "created" timestamp NOT NULL,
  "edited" timestamp NOT NULL,
  "start" timestamp NOT NULL,
  "finish" timestamp NOT NULL,
  "summary" text NOT NULL,
  "description" text NOT NULL,
  "location" text NOT NULL,
  "type" text NOT NULL,
  "nofinish" numeric(1) NOT NULL DEFAULT '0',
  "adjust" numeric(1) NOT NULL DEFAULT '1',
  "ignore" numeric(1) NOT NULL DEFAULT '0',
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "event_uid_idx" on event ("uid");
create index "event_type_idx" on event ("type");
create index "event_start_idx" on event ("start");
create index "event_finish_idx" on event ("finish");
create index "event_adjust_idx" on event ("adjust");
create index "event_nofinish_idx" on event ("nofinish");
create index "event_ignore_idx" on event ("ignore");
create index "event_aid_idx" on event ("aid");
create index "event_hash_idx" on event ("event_hash");
create index "event_xchan_idx" on event ("event_xchan");


CREATE TABLE "fcontact" (
  "id" serial  NOT NULL,
  "url" text NOT NULL,
  "name" text NOT NULL,
  "photo" text NOT NULL,
  "request" text NOT NULL,
  "nick" text NOT NULL,
  "addr" text NOT NULL,
  "batch" text NOT NULL,
  "notify" text NOT NULL,
  "poll" text NOT NULL,
  "confirm" text NOT NULL,
  "priority" numeric(1) NOT NULL,
  "network" varchar(32) NOT NULL DEFAULT '',
  "alias" text NOT NULL,
  "pubkey" text NOT NULL,
  "updated" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("id")
);
create index "fcontact_addr_idx" on fcontact ("addr");
create index "fcontact_network_idx" on fcontact ("network");

CREATE TABLE "ffinder" (
  "id" serial  NOT NULL,
  "uid" bigint  NOT NULL,
  "cid" bigint  NOT NULL,
  "fid" bigint  NOT NULL,
  PRIMARY KEY ("id")
);
create index "ffinder_uid_idx" on ffinder ("uid");
create index "ffinder_cid_idx" on ffinder ("cid");
create index "ffinder_fid_idx" on ffinder ("fid");

CREATE TABLE "fserver" (
  "id" serial NOT NULL,
  "server" text NOT NULL,
  "posturl" text NOT NULL,
  "key" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "fserver_server_idx" on fserver ("server");
create index "fserver_posturl_idx" on fserver ("posturl");

CREATE TABLE "fsuggest" (
  "id" serial NOT NULL,
  "uid" bigint NOT NULL,
  "cid" bigint NOT NULL,
  "name" text NOT NULL,
  "url" text NOT NULL,
  "request" text NOT NULL,
  "photo" text NOT NULL,
  "note" text NOT NULL,
  "created" timestamp NOT NULL,
  PRIMARY KEY ("id")
);
CREATE TABLE "group_member" (
  "id" serial  NOT NULL,
  "uid" bigint  NOT NULL,
  "gid" bigint  NOT NULL,
  "xchan" text NOT NULL DEFAULT '',
  PRIMARY KEY ("id")
);
create index "groupmember_uid" on group_member ("uid");
create index "groupmember_gid" on group_member ("gid");
create index "groupmember_xchan" on group_member ("xchan");

CREATE TABLE "groups" (
  "id" serial  NOT NULL,
  "hash" text NOT NULL DEFAULT '',
  "uid" bigint  NOT NULL,
  "visible" numeric(1) NOT NULL DEFAULT '0',
  "deleted" numeric(1) NOT NULL DEFAULT '0',
  "name" text NOT NULL,
  PRIMARY KEY ("id")

);
create index "groups_uid_idx" on groups ("uid");
create index "groups_visible_idx" on groups  ("visible");
create index "groups_deleted_idx" on groups ("deleted");
create index "groups_hash_idx" on groups ("hash");

CREATE TABLE "hook" (
  "id" serial NOT NULL,
  "hook" text NOT NULL,
  "file" text NOT NULL,
  "function" text NOT NULL,
  "priority" bigint  NOT NULL DEFAULT '0',
  PRIMARY KEY ("id")

);
create index "hook_idx" on hook ("hook");
CREATE TABLE "hubloc" (
  "hubloc_id" serial  NOT NULL,
  "hubloc_guid" text NOT NULL DEFAULT '',
  "hubloc_guid_sig" text NOT NULL DEFAULT '',
  "hubloc_hash" text NOT NULL,
  "hubloc_addr" text NOT NULL DEFAULT '',
  "hubloc_network" text NOT NULL DEFAULT '',
  "hubloc_flags" bigint  NOT NULL DEFAULT '0',
  "hubloc_status" bigint  NOT NULL DEFAULT '0',
  "hubloc_url" text NOT NULL DEFAULT '',
  "hubloc_url_sig" text NOT NULL DEFAULT '',
  "hubloc_host" text NOT NULL DEFAULT '',
  "hubloc_callback" text NOT NULL DEFAULT '',
  "hubloc_connect" text NOT NULL DEFAULT '',
  "hubloc_sitekey" text NOT NULL DEFAULT '',
  "hubloc_updated" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "hubloc_connected" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("hubloc_id")
);
create index "hubloc_url" on hubloc ("hubloc_url");
create index "hubloc_guid" on hubloc ("hubloc_guid");
create index "hubloc_flags" on hubloc ("hubloc_flags");
create index "hubloc_connect" on hubloc ("hubloc_connect");
create index "hubloc_host" on hubloc ("hubloc_host");
create index "hubloc_addr" on hubloc ("hubloc_addr");
create index "hubloc_network" on hubloc ("hubloc_network");
create index "hubloc_updated" on hubloc ("hubloc_updated");
create index "hubloc_connected" on hubloc ("hubloc_connected");
create index "hubloc_status" on hubloc ("hubloc_status");
CREATE TABLE "issue" (
  "issue_id" serial  NOT NULL,
  "issue_created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "issue_updated" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "issue_assigned" text NOT NULL,
  "issue_priority" bigint NOT NULL,
  "issue_status" bigint NOT NULL,
  "issue_component" text NOT NULL,
  PRIMARY KEY ("issue_id")
);
create index "issue_created" on issue ("issue_created");
create index "issue_updated" on issue ("issue_updated");
create index "issue_assigned" on issue ("issue_assigned");
create index "issue_priority" on issue ("issue_priority");
create index "issue_status" on issue ("issue_status");
create index "issue_component" on issue ("issue_component");

CREATE TABLE "item" (
  "id" serial  NOT NULL,
  "mid" text  NOT NULL DEFAULT '',
  "aid" bigint  NOT NULL DEFAULT '0',
  "uid" bigint  NOT NULL DEFAULT '0',
  "parent" bigint  NOT NULL DEFAULT '0',
  "parent_mid" text  NOT NULL DEFAULT '',
  "thr_parent" text NOT NULL DEFAULT '',
  "created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "edited" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "expires" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "commented" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "received" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "changed" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "comments_closed" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "owner_xchan" text NOT NULL DEFAULT '',
  "author_xchan" text NOT NULL DEFAULT '',
  "source_xchan" text NOT NULL DEFAULT '',
  "mimetype" text NOT NULL DEFAULT '',
  "title" text NOT NULL,
  "body" text NOT NULL,
  "app" text NOT NULL DEFAULT '',
  "lang" varchar(64) NOT NULL DEFAULT '',
  "revision" bigint  NOT NULL DEFAULT '0',
  "verb" text NOT NULL DEFAULT '',
  "obj_type" text NOT NULL DEFAULT '',
  "object" text NOT NULL,
  "tgt_type" text NOT NULL DEFAULT '',
  "target" text NOT NULL,
  "layout_mid" text NOT NULL DEFAULT '',
  "postopts" text NOT NULL DEFAULT '',
  "route" text NOT NULL DEFAULT '',
  "llink" text NOT NULL DEFAULT '',
  "plink" text NOT NULL DEFAULT '',
  "resource_id" text NOT NULL DEFAULT '',
  "resource_type" varchar(16) NOT NULL DEFAULT '',
  "attach" text NOT NULL,
  "sig" text NOT NULL DEFAULT '',
  "diaspora_meta" text NOT NULL DEFAULT '',
  "location" text NOT NULL DEFAULT '',
  "coord" text NOT NULL DEFAULT '',
  "public_policy" text NOT NULL DEFAULT '',
  "comment_policy" text NOT NULL DEFAULT '',
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  "item_restrict" bigint NOT NULL DEFAULT '0',
  "item_flags" bigint NOT NULL DEFAULT '0',
  "item_private" numeric(4) NOT NULL DEFAULT '0',
  "item_search_vector" tsvector,
  PRIMARY KEY ("id")
);
create index "item_uid" on item ("uid");
create index "item_parent" on item ("parent");
create index "item_created" on item ("created");
create index "item_edited" on item ("edited");
create index "item_received" on item ("received");
create index "item_uid_commented" on item ("uid","commented");
create index "item_uid_created" on item ("uid","created");
create index "item_changed" on item ("changed");
create index "item_comments_closed" on item ("comments_closed");
create index "item_aid" on item ("aid");
create index "item_owner_xchan" on item ("owner_xchan");
create index "item_author_xchan" on item ("author_xchan");
create index "item_resource_type" on item ("resource_type");
create index "item_restrict" on item ("item_restrict");
create index "item_flags" on item ("item_flags");
create index "item_commented" on item ("commented");
create index "item_verb" on item ("verb");
create index "item_private" on item ("item_private");
create index "item_llink" on item ("llink");
create index "item_expires" on item ("expires");
create index "item_revision" on item ("revision");
create index "item_mimetype" on item ("mimetype");
create index "item_mid" on item ("mid");
create index "item_parent_mid" on item ("parent_mid");
create index "item_uid_mid" on item ("mid","uid");
create index "item_public_policy" on item ("public_policy");
create index "item_comment_policy" on item ("comment_policy");
create index "item_layout_mid" on item ("layout_mid");

-- fulltext indexes
create index "item_search_idx" on  item USING gist("item_search_vector");
create index "item_allow_cid" on item ("allow_cid");
create index "item_allow_gid" on item ("allow_gid");
create index "item_deny_cid" on item ("deny_cid");
create index "item_deny_gid" on item ("deny_gid");

CREATE TABLE "item_id" (
  "id" serial  NOT NULL,
  "iid" bigint NOT NULL,
  "uid" bigint NOT NULL,
  "sid" text NOT NULL,
  "service" text NOT NULL,
  PRIMARY KEY ("id")

);
create index "itemid_uid" on item_id ("uid");
create index "itemid_sid" on item_id ("sid");
create index "itemid_service" on item_id ("service");
create index "itemid_iid" on item_id ("iid");
CREATE TABLE "likes" (
  "id" serial  NOT NULL,
  "channel_id" bigint  NOT NULL DEFAULT '0',
  "liker" char(128) NOT NULL DEFAULT '',
  "likee" char(128) NOT NULL DEFAULT '',
  "iid" bigint  NOT NULL DEFAULT '0',
  "verb" text NOT NULL DEFAULT '',
  "target_type" text NOT NULL DEFAULT '',
  "target_id" char(128) NOT NULL DEFAULT '',
  "target" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "likes_channel_id" on likes ("channel_id");
create index "likes_liker" on likes ("liker");
create index "likes_likee" on likes ("likee");
create index "likes_iid" on likes ("iid");
create index "likes_verb" on likes ("verb");
create index "likes_target_type" on likes ("target_type");
create index "likes_target_id" on likes ("target_id");
CREATE TABLE "mail" (
  "id" serial  NOT NULL,
  "convid" bigint  NOT NULL DEFAULT '0',
  "mail_flags" bigint  NOT NULL DEFAULT '0',
  "from_xchan" text NOT NULL DEFAULT '',
  "to_xchan" text NOT NULL DEFAULT '',
  "account_id" bigint  NOT NULL DEFAULT '0',
  "channel_id" bigint  NOT NULL,
  "title" text NOT NULL,
  "body" text NOT NULL,
  "attach" text NOT NULL DEFAULT '',
  "mid" text NOT NULL,
  "parent_mid" text NOT NULL,
  "created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "expires" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("id")
);
create index "mail_convid" on mail ("convid");
create index "mail_created" on mail ("created");
create index "mail_flags" on mail ("mail_flags");
create index "mail_account_id" on mail ("account_id");
create index "mail_channel_id" on mail ("channel_id");
create index "mail_from_xchan" on mail ("from_xchan");
create index "mail_to_xchan" on mail ("to_xchan");
create index "mail_mid" on mail ("mid");
create index "mail_parent_mid" on mail ("parent_mid");
create index "mail_expires" on mail ("expires");
CREATE TABLE "manage" (
  "id" serial NOT NULL,
  "uid" bigint NOT NULL,
  "xchan" text NOT NULL DEFAULT '',
  PRIMARY KEY ("id")

);
create index "manage_uid" on manage ("uid");
create index "manage_xchan" on manage ("xchan");
CREATE TABLE "menu" (
  "menu_id" serial  NOT NULL,
  "menu_channel_id" bigint  NOT NULL DEFAULT '0',
  "menu_name" text NOT NULL DEFAULT '',
  "menu_desc" text NOT NULL DEFAULT '',
  "menu_flags" bigint NOT NULL DEFAULT '0',
  PRIMARY KEY ("menu_id")
);
create index "menu_channel_id" on menu ("menu_channel_id");
create index "menu_name" on menu ("menu_name");
create index "menu_flags" on menu ("menu_flags");
CREATE TABLE "menu_item" (
  "mitem_id" serial  NOT NULL,
  "mitem_link" text NOT NULL DEFAULT '',
  "mitem_desc" text NOT NULL DEFAULT '',
  "mitem_flags" bigint NOT NULL DEFAULT '0',
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  "mitem_channel_id" bigint  NOT NULL,
  "mitem_menu_id" bigint  NOT NULL DEFAULT '0',
  "mitem_order" bigint NOT NULL DEFAULT '0',
  PRIMARY KEY ("mitem_id")

);
create index "mitem_channel_id" on menu_item ("mitem_channel_id");
create index "mitem_menu_id" on menu_item ("mitem_menu_id");
create index "mitem_flags" on menu_item ("mitem_flags");
CREATE TABLE "notify" (
  "id" serial NOT NULL,
  "hash" char(64) NOT NULL,
  "name" text NOT NULL,
  "url" text NOT NULL,
  "photo" text NOT NULL,
  "date" timestamp NOT NULL,
  "msg" text NOT NULL DEFAULT '',
  "aid" bigint NOT NULL,
  "uid" bigint NOT NULL,
  "link" text NOT NULL,
  "parent" text NOT NULL DEFAULT '',
  "seen" numeric(1) NOT NULL DEFAULT '0',
  "type" bigint NOT NULL,
  "verb" text NOT NULL,
  "otype" varchar(16) NOT NULL,
  PRIMARY KEY ("id")
);
create index "notify_type" on notify ("type");
create index "notify_seen" on notify ("seen");
create index "notify_uid" on notify ("uid");
create index "notify_date" on notify ("date");
create index "notify_hash" on notify ("hash");
create index "notify_parent" on notify ("parent");
create index "notify_link" on notify ("link");
create index "notify_otype" on notify ("otype");
create index "notify_aid" on notify ("aid");
CREATE TABLE "obj" (
  "obj_id" serial  NOT NULL,
  "obj_page" char(64) NOT NULL DEFAULT '',
  "obj_verb" text NOT NULL DEFAULT '',
  "obj_type" bigint  NOT NULL DEFAULT '0',
  "obj_obj" text NOT NULL DEFAULT '',
  "obj_channel" bigint  NOT NULL DEFAULT '0',
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  PRIMARY KEY ("obj_id")

);
create index "obj_verb" on obj ("obj_verb");
create index "obj_page" on obj ("obj_page");
create index "obj_type" on obj ("obj_type");
create index "obj_channel" on obj ("obj_channel");
create index "obj_obj" on obj ("obj_obj");

CREATE TABLE "outq" (
  "outq_hash" text NOT NULL,
  "outq_account" bigint  NOT NULL DEFAULT '0',
  "outq_channel" bigint  NOT NULL DEFAULT '0',
  "outq_driver" varchar(32) NOT NULL DEFAULT '',
  "outq_posturl" text NOT NULL DEFAULT '',
  "outq_async" numeric(1) NOT NULL DEFAULT '0',
  "outq_delivered" numeric(1) NOT NULL DEFAULT '0',
  "outq_created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "outq_updated" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "outq_notify" text NOT NULL,
  "outq_msg" text NOT NULL,
  PRIMARY KEY ("outq_hash")
);
create index "outq_account" on outq ("outq_account");
create index "outq_channel" on outq ("outq_channel");
create index "outq_hub" on outq ("outq_posturl");
create index "outq_created" on outq ("outq_created");
create index "outq_updated" on outq ("outq_updated");
create index "outq_async" on outq ("outq_async");
create index "outq_delivered" on outq ("outq_delivered");

CREATE TABLE "pconfig" (
  "id" serial NOT NULL,
  "uid" bigint NOT NULL DEFAULT '0',
  "cat" text  NOT NULL,
  "k" text  NOT NULL,
  "v" text NOT NULL,
  PRIMARY KEY ("id"),
  UNIQUE ("uid","cat","k")
);
CREATE TABLE "photo" (
  "id" serial  NOT NULL,
  "aid" bigint  NOT NULL DEFAULT '0',
  "uid" bigint  NOT NULL,
  "xchan" text NOT NULL DEFAULT '',
  "resource_id" text NOT NULL,
  "created" timestamp NOT NULL,
  "edited" timestamp NOT NULL,
  "title" text NOT NULL,
  "description" text NOT NULL,
  "album" text NOT NULL,
  "filename" text NOT NULL,
  "type" varchar(128) NOT NULL DEFAULT 'image/jpeg',
  "height" numeric(6) NOT NULL,
  "width" numeric(6) NOT NULL,
  "size" bigint  NOT NULL DEFAULT '0',
  "data" bytea NOT NULL,
  "scale" numeric(3) NOT NULL,
  "profile" numeric(1) NOT NULL DEFAULT '0',
  "photo_flags" bigint  NOT NULL DEFAULT '0',
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "photo_uid" on photo ("uid");
create index "photo_album" on photo ("album");
create index "photo_scale" on photo ("scale");
create index "photo_profile" on photo ("profile");
create index "photo_flags" on photo ("photo_flags");
create index "photo_type" on photo ("type");
create index "photo_aid" on photo ("aid");
create index "photo_xchan" on photo ("xchan");
create index "photo_size" on photo ("size");
create index "photo_resource_id" on photo ("resource_id");

CREATE TABLE "poll" (
  "poll_id" serial  NOT NULL,
  "poll_channel" bigint  NOT NULL DEFAULT '0',
  "poll_desc" text NOT NULL,
  "poll_flags" bigint NOT NULL DEFAULT '0',
  "poll_votes" bigint NOT NULL DEFAULT '0',
  PRIMARY KEY ("poll_id")

);
create index "poll_channel" on poll ("poll_channel");
create index "poll_flags" on poll ("poll_flags");
create index "poll_votes" on poll ("poll_votes");
CREATE TABLE "poll_elm" (
  "pelm_id" serial  NOT NULL,
  "pelm_poll" bigint  NOT NULL DEFAULT '0',
  "pelm_desc" text NOT NULL,
  "pelm_flags" bigint NOT NULL DEFAULT '0',
  "pelm_result" float NOT NULL DEFAULT '0',
  PRIMARY KEY ("pelm_id")
);
create index "pelm_poll" on poll_elm ("pelm_poll");
create index "pelm_result" on poll_elm ("pelm_result");

CREATE TABLE "profdef" (
  "id" serial  NOT NULL,
  "field_name" text NOT NULL DEFAULT '',
  "field_type" varchar(16) NOT NULL DEFAULT '',
  "field_desc" text NOT NULL DEFAULT '',
  "field_help" text NOT NULL DEFAULT '',
  "field_inputs" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "profdef_field_name" on profdef ("field_name");
CREATE TABLE "profext" (
  "id" serial  NOT NULL,
  "channel_id" bigint  NOT NULL DEFAULT '0',
  "hash" text NOT NULL DEFAULT '',
  "k" text NOT NULL DEFAULT '',
  "v" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "profext_channel_id" on profext ("channel_id");
create index "profext_hash" on profext ("hash");
create index "profext_k" on profext ("k");

CREATE TABLE "profile" (
  "id" serial NOT NULL,
  "profile_guid" char(64) NOT NULL DEFAULT '',
  "aid" bigint  NOT NULL DEFAULT '0',
  "uid" bigint NOT NULL,
  "profile_name" text NOT NULL,
  "is_default" numeric(1) NOT NULL DEFAULT '0',
  "hide_friends" numeric(1) NOT NULL DEFAULT '0',
  "name" text NOT NULL,
  "pdesc" text NOT NULL DEFAULT '',
  "chandesc" text NOT NULL DEFAULT '',
  "dob" varchar(32) NOT NULL DEFAULT '',
  "dob_tz" text NOT NULL DEFAULT 'UTC',
  "address" text NOT NULL DEFAULT '',
  "locality" text NOT NULL DEFAULT '',
  "region" text NOT NULL DEFAULT '',
  "postal_code" varchar(32) NOT NULL DEFAULT '',
  "country_name" text NOT NULL DEFAULT '',
  "hometown" text NOT NULL DEFAULT '',
  "gender" varchar(32) NOT NULL DEFAULT '',
  "marital" text NOT NULL DEFAULT '',
  "with" text NOT NULL DEFAULT '',
  "howlong" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "sexual" text NOT NULL DEFAULT '',
  "politic" text NOT NULL DEFAULT '',
  "religion" text NOT NULL DEFAULT '',
  "keywords" text NOT NULL DEFAULT '',
  "likes" text NOT NULL DEFAULT '',
  "dislikes" text NOT NULL DEFAULT '',
  "about" text NOT NULL DEFAULT '',
  "summary" text NOT NULL DEFAULT '',
  "music" text NOT NULL DEFAULT '',
  "book" text NOT NULL DEFAULT '',
  "tv" text NOT NULL DEFAULT '',
  "film" text NOT NULL DEFAULT '',
  "interest" text NOT NULL DEFAULT '',
  "romance" text NOT NULL DEFAULT '',
  "work" text NOT NULL DEFAULT '',
  "education" text NOT NULL DEFAULT '',
  "contact" text NOT NULL DEFAULT '',
  "channels" text NOT NULL DEFAULT '',
  "homepage" text NOT NULL DEFAULT '',
  "photo" text NOT NULL,
  "thumb" text NOT NULL,
  "publish" numeric(1) NOT NULL DEFAULT '0',
  PRIMARY KEY ("id"),
  UNIQUE ("profile_guid","uid")

);
create index "profile_uid" on profile ("uid");
create index "profile_locality" on profile ("locality");
create index "profile_hometown" on profile ("hometown");
create index "profile_gender" on profile ("gender");
create index "profile_marital" on profile ("marital");
create index "profile_sexual" on profile ("sexual");
create index "profile_publish" on profile ("publish");
create index "profile_aid" on profile ("aid");
create index "profile_is_default" on profile ("is_default");
create index "profile_hide_friends" on profile ("hide_friends");
create index "profile_postal_code" on profile ("postal_code");
create index "profile_country_name" on profile ("country_name");
create index "profile_guid" on profile ("profile_guid");
CREATE TABLE "profile_check" (
  "id" serial  NOT NULL,
  "uid" bigint  NOT NULL,
  "cid" bigint  NOT NULL DEFAULT '0',
  "dfrn_id" text NOT NULL,
  "sec" text NOT NULL,
  "expire" bigint NOT NULL,
  PRIMARY KEY ("id")
);
create index "pc_uid" on profile_check ("uid");
create index "pc_cid" on profile_check ("cid");
create index "pc_dfrn_id" on profile_check ("dfrn_id");
create index "pc_sec" on profile_check ("sec");
create index "pc_expire" on profile_check ("expire");

CREATE TABLE "register" (
  "id" serial  NOT NULL,
  "hash" text NOT NULL,
  "created" timestamp NOT NULL,
  "uid" bigint  NOT NULL,
  "password" text NOT NULL,
  "language" varchar(16) NOT NULL,
  PRIMARY KEY ("id")
);
create index "reg_hash" on register ("hash");
create index "reg_created" on register ("created");
create index "reg_uid" on register ("uid");
CREATE TABLE "session" (
  "id" serial,
  "sid" text NOT NULL,
  "data" text NOT NULL,
  "expire" numeric(20)  NOT NULL,
  PRIMARY KEY ("id")
);
create index "session_sid" on session ("sid");
create index "session_expire" on session ("expire");
CREATE TABLE "shares" (
  "share_id" serial  NOT NULL,
  "share_type" bigint NOT NULL DEFAULT '0',
  "share_target" bigint  NOT NULL DEFAULT '0',
  "share_xchan" text NOT NULL DEFAULT '',
  PRIMARY KEY ("share_id")
);
create index "share_type" on shares ("share_type");
create index "share_target" on shares ("share_target");
create index "share_xchan" on shares ("share_xchan");

CREATE TABLE "sign" (
  "id" serial  NOT NULL,
  "iid" bigint  NOT NULL DEFAULT '0',
  "retract_iid" bigint  NOT NULL DEFAULT '0',
  "signed_text" text NOT NULL,
  "signature" text NOT NULL,
  "signer" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "sign_iid" on "sign" ("iid");
create index "sign_retract_iid" on "sign" ("retract_iid");

CREATE TABLE "site" (
  "site_url" text NOT NULL,
  "site_access" bigint NOT NULL DEFAULT '0',
  "site_flags" bigint NOT NULL DEFAULT '0',
  "site_update" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "site_pull" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "site_sync" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "site_directory" text NOT NULL DEFAULT '',
  "site_register" bigint NOT NULL DEFAULT '0',
  "site_sellpage" text NOT NULL DEFAULT '',
  "site_location" text NOT NULL DEFAULT '',
  "site_realm" text NOT NULL DEFAULT '',
  PRIMARY KEY ("site_url")
);
create index "site_flags" on site ("site_flags");
create index "site_update" on site  ("site_update");
create index "site_directory" on site ("site_directory");
create index "site_register" on site ("site_register");
create index "site_access" on site ("site_access");
create index "site_sellpage" on site ("site_sellpage");
create index "site_realm" on site ("site_realm");

CREATE TABLE "source" (
  "src_id" serial  NOT NULL,
  "src_channel_id" bigint  NOT NULL DEFAULT '0',
  "src_channel_xchan" text NOT NULL DEFAULT '',
  "src_xchan" text NOT NULL DEFAULT '',
  "src_patt" text NOT NULL,
  PRIMARY KEY ("src_id")
);
create index "src_channel_id" on "source" ("src_channel_id");
create index "src_channel_xchan" on "source"  ("src_channel_xchan");
create index "src_xchan" on "source" ("src_xchan");
CREATE TABLE "spam" (
  "id" serial NOT NULL,
  "uid" bigint NOT NULL,
  "spam" bigint NOT NULL DEFAULT '0',
  "ham" bigint NOT NULL DEFAULT '0',
  "term" text NOT NULL,
  "date" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("id")
);
create index "spam_uid" on spam ("uid");
create index "spam_spam" on spam ("spam");
create index "spam_ham" on spam ("ham");
create index "spam_term" on spam ("term");
CREATE TABLE "sys_perms" (
  "id" serial  NOT NULL,
  "cat" text NOT NULL,
  "k" text NOT NULL,
  "v" text NOT NULL,
  "public_perm" numeric(1)  NOT NULL,
  PRIMARY KEY ("id")
);
CREATE TABLE "term" (
  "tid" serial  NOT NULL,
  "aid" bigint  NOT NULL DEFAULT '0',
  "uid" bigint  NOT NULL DEFAULT '0',
  "oid" bigint  NOT NULL,
  "otype" numeric(3)  NOT NULL,
  "type" numeric(3)  NOT NULL,
  "term" text NOT NULL,
  "url" text NOT NULL,
  "imgurl" text NOT NULL DEFAULT '',
  "term_hash" text NOT NULL DEFAULT '',
  "parent_hash" text NOT NULL DEFAULT '',
  PRIMARY KEY ("tid")
);
create index "term_oid" on term ("oid");
create index "term_otype" on term ("otype");
create index "term_type" on term ("type");
create index "term_term" on term ("term");
create index "term_uid" on term ("uid");
create index "term_aid" on term ("aid");
create index "term_imgurl" on term ("imgurl");
create index "term_hash" on term ("term_hash");
create index "term_parent_hash" on term ("parent_hash");
CREATE TABLE "tokens" (
  "id" varchar(40) NOT NULL,
  "secret" text NOT NULL,
  "client_id" varchar(20) NOT NULL,
  "expires" numeric(20)  NOT NULL,
  "scope" varchar(200) NOT NULL,
  "uid" bigint NOT NULL,
  PRIMARY KEY ("id")
);
create index "tokens_client_id" on tokens ("client_id");
create index "tokens_expires" on tokens ("expires");
create index "tokens_uid" on tokens ("uid");

CREATE TABLE "updates" (
  "ud_id" serial  NOT NULL,
  "ud_hash" char(128) NOT NULL,
  "ud_guid" text NOT NULL DEFAULT '',
  "ud_date" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "ud_last" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "ud_flags" bigint NOT NULL DEFAULT '0',
  "ud_addr" text NOT NULL DEFAULT '',
  PRIMARY KEY ("ud_id")
);
create index "ud_date" on updates ("ud_date");
create index "ud_guid" on updates ("ud_guid");
create index "ud_hash" on updates ("ud_hash");
create index "ud_flags" on updates ("ud_flags");
create index "ud_addr" on updates ("ud_addr");
create index "ud_last" on updates ("ud_last");
CREATE TABLE "verify" (
  "id" serial  NOT NULL,
  "channel" bigint  NOT NULL DEFAULT '0',
  "type" varchar(32) NOT NULL DEFAULT '',
  "token" text NOT NULL DEFAULT '',
  "meta" text NOT NULL DEFAULT '',
  "created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("id")
);
create index "verify_channel" on verify ("channel");
create index "verify_type" on verify ("type");
create index "verify_token" on verify ("token");
create index "verify_meta" on verify ("meta");
create index "verify_created" on verify ("created");
CREATE TABLE "vote" (
  "vote_id" serial  NOT NULL,
  "vote_poll" bigint NOT NULL DEFAULT '0',
  "vote_element" bigint NOT NULL DEFAULT '0',
  "vote_result" text NOT NULL,
  "vote_xchan" text NOT NULL DEFAULT '',
  PRIMARY KEY ("vote_id"),
  UNIQUE ("vote_poll","vote_element","vote_xchan")
);
create index "vote_poll" on vote ("vote_poll");
create index "vote_element" on vote ("vote_element");
CREATE TABLE "xchan" (
  "xchan_hash" text NOT NULL,
  "xchan_guid" text NOT NULL DEFAULT '',
  "xchan_guid_sig" text NOT NULL DEFAULT '',
  "xchan_pubkey" text NOT NULL DEFAULT '',
  "xchan_photo_mimetype" text NOT NULL DEFAULT 'image/jpeg',
  "xchan_photo_l" text NOT NULL DEFAULT '',
  "xchan_photo_m" text NOT NULL DEFAULT '',
  "xchan_photo_s" text NOT NULL DEFAULT '',
  "xchan_addr" text NOT NULL DEFAULT '',
  "xchan_url" text NOT NULL DEFAULT '',
  "xchan_connurl" text NOT NULL DEFAULT '',
  "xchan_follow" text NOT NULL DEFAULT '',
  "xchan_connpage" text NOT NULL DEFAULT '',
  "xchan_name" text NOT NULL DEFAULT '',
  "xchan_network" text NOT NULL DEFAULT '',
  "xchan_instance_url" text NOT NULL DEFAULT '',
  "xchan_flags" bigint  NOT NULL DEFAULT '0',
  "xchan_photo_date" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "xchan_name_date" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("xchan_hash")
);
create index "xchan_guid" on xchan ("xchan_guid");
create index "xchan_addr" on xchan ("xchan_addr");
create index "xchan_name" on xchan ("xchan_name");
create index "xchan_network" on xchan ("xchan_network");
create index "xchan_url" on xchan ("xchan_url");
create index "xchan_flags" on xchan ("xchan_flags");
create index "xchan_connurl" on xchan ("xchan_connurl");
create index "xchan_instance_url" on xchan ("xchan_instance_url");
create index "xchan_follow" on xchan ("xchan_follow");
CREATE TABLE "xchat" (
  "xchat_id" serial  NOT NULL,
  "xchat_url" text NOT NULL DEFAULT '',
  "xchat_desc" text NOT NULL DEFAULT '',
  "xchat_xchan" text NOT NULL DEFAULT '',
  "xchat_edited" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("xchat_id")
);
create index "xchat_url" on xchat ("xchat_url");
create index "xchat_desc" on xchat ("xchat_desc");
create index "xchat_xchan" on xchat ("xchat_xchan");
create index "xchat_edited" on xchat ("xchat_edited");
CREATE TABLE "xconfig" (
  "id" serial  NOT NULL,
  "xchan" text NOT NULL,
  "cat" text NOT NULL,
  "k" text NOT NULL,
  "v" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "xconfig_xchan" on xconfig ("xchan");
create index "xconfig_cat" on xconfig ("cat");
create index "xconfig_k" on xconfig ("k");
CREATE TABLE "xign" (
  "id" serial  NOT NULL,
  "uid" bigint NOT NULL DEFAULT '0',
  "xchan" text NOT NULL DEFAULT '',
  PRIMARY KEY ("id")
);
create index "xign_uid" on xign ("uid");
create index "xign_xchan" on xign ("xchan");
CREATE TABLE "xlink" (
  "xlink_id" serial  NOT NULL,
  "xlink_xchan" text NOT NULL DEFAULT '',
  "xlink_link" text NOT NULL DEFAULT '',
  "xlink_rating" bigint NOT NULL DEFAULT '0',
  "xlink_rating_text" TEXT NOT NULL DEFAULT '',
  "xlink_updated" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "xlink_static" numeric(1) NOT NULL DEFAULT '0',
  PRIMARY KEY ("xlink_id")
);
create index "xlink_xchan" on xlink ("xlink_xchan");
create index "xlink_link" on xlink ("xlink_link");
create index "xlink_updated" on xlink ("xlink_updated");
create index "xlink_rating" on xlink ("xlink_rating");
create index "xlink_static" on xlink ("xlink_static");
CREATE TABLE "xperm" (
  "xp_id" serial NOT NULL,
  "xp_client" varchar( 20 ) NOT NULL DEFAULT '',
  "xp_channel" bigint NOT NULL DEFAULT '0',
  "xp_perm" varchar( 64 ) NOT NULL DEFAULT '',
  PRIMARY_KEY ("xp_id")
);
create index "xp_client" on xperm ("xp_client");
create index "xp_channel" on xperm ("xp_channel");
create index "xp_perm" on xperm ("xp_perm");
CREATE TABLE "xprof" (
  "xprof_hash" text NOT NULL,
  "xprof_age" numeric(3)  NOT NULL DEFAULT '0',
  "xprof_desc" text NOT NULL DEFAULT '',
  "xprof_dob" varchar(12) NOT NULL DEFAULT '',
  "xprof_gender" text NOT NULL DEFAULT '',
  "xprof_marital" text NOT NULL DEFAULT '',
  "xprof_sexual" text NOT NULL DEFAULT '',
  "xprof_locale" text NOT NULL DEFAULT '',
  "xprof_region" text NOT NULL DEFAULT '',
  "xprof_postcode" varchar(32) NOT NULL DEFAULT '',
  "xprof_country" text NOT NULL DEFAULT '',
  "xprof_keywords" text NOT NULL,
  "xprof_about" text NOT NULL,
  "xprof_homepage" text NOT NULL DEFAULT '',
  "xprof_hometown" text NOT NULL DEFAULT '',
  PRIMARY KEY ("xprof_hash")
);
create index "xprof_desc" on xprof ("xprof_desc");
create index "xprof_dob" on xprof ("xprof_dob");
create index "xprof_gender" on xprof ("xprof_gender");
create index "xprof_marital" on xprof ("xprof_marital");
create index "xprof_sexual" on xprof ("xprof_sexual");
create index "xprof_locale" on xprof ("xprof_locale");
create index "xprof_region" on xprof ("xprof_region");
create index "xprof_postcode" on xprof ("xprof_postcode");
create index "xprof_country" on xprof ("xprof_country");
create index "xprof_age" on xprof ("xprof_age");
create index "xprof_hometown" on xprof ("xprof_hometown");
CREATE TABLE "xtag" (
  "xtag_id" serial  NOT NULL,
  "xtag_hash" text NOT NULL,
  "xtag_term" text NOT NULL DEFAULT '',
  "xtag_flags" bigint NOT NULL DEFAULT '0',
  PRIMARY KEY ("xtag_id")
);
create index "xtag_term" on xtag ("xtag_term");
create index "xtag_hash" on xtag ("xtag_hash");
create index "xtag_flags" on xtag ("xtag_flags");
