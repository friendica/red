<?xml version='1.0' encoding='UTF-8'?>
<XRD xmlns='http://docs.oasis-open.org/ns/xri/xrd-1.0'
     xmlns:hm='http://host-meta.net/xrd/1.0'>
 
    <hm:Host>$zroot</hm:Host>
 
    <Link rel='lrdd' template='$domain/xrd/?uri={uri}' />
    <Link rel='acct-mgmt' href='$domain/amcd' />
    <Link rel='http://services.mozilla.com/amcd/0.1' href='$domain/amcd' />
	<Link rel="http://oexchange.org/spec/0.8/rel/resident-target" type="application/xrd+xml" 
		href="$domain/oexchange/xrd" />
</XRD>
