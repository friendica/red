<?php /** @file */

/*
 * Abstraction class for dealing with alternate networks (which of course do not exist, hence the abstraction)
 */


abstract class ProtoDriver {
	abstract protected function discover($channel,$location);
	abstract protected function deliver($item,$channel,$recipients);
	abstract protected function collect($channel,$connection);
	abstract protected function change_permissions($permissions,$channel,$recipient);
	abstract protected function acknowledge_permissions($permissions,$channel,$recipient);
	abstract protected function deliver_private($item,$channel,$recipients);
	abstract protected function collect_private($channel,$connection);

}

class ZotDriver extends ProtoDriver {

	protected function discover($channel,$location) {

	}
	protected function deliver($item,$channel,$recipients) {

	}
	protected function collect($channel,$connection) {

	}
	protected function change_permissions($permissions,$channel,$recipient) {

	}
	protected function acknowledge_permissions($permissions,$channel,$recipient) {

	}
	protected function deliver_private($item,$channel,$recipients) {

	}
	protected function collect_private($channel,$connection) {

	}

}
