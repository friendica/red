<?xml version='1.0' encoding='UTF-8'?>
<XRD xmlns='http://docs.oasis-open.org/ns/xri/xrd-1.0'
     xmlns:hm='http://host-meta.net/xrd/1.0'>
 
    <hm:Host>{{$zhost}}</hm:Host>
 
    <Link rel='lrdd' template='{{$zroot}}/xrd/?uri={uri}' />
	<Link rel="http://oexchange.org/spec/0.8/rel/resident-target" type="application/xrd+xml" 
        href="{{$zroot}}/oexchange/xrd" />

</XRD>
