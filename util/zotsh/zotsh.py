#!/usr/bin/env python2
import sys, os
import ConfigParser
import requests
from requests.auth import HTTPBasicAuth
import  easywebdav
import easywebdav.__version__ as easywebdavversion

__version__= "0.0.2"

SERVER = None
USER = None
PASSWD = None
VERIFY_SSL=True

#####################################################

class CommandNotFound(Exception):
    pass

class ZotSH(object):
    commands = ['cd','ls','exists','mkdir','mkdirs','rmdir','delete','upload','download',
                        'host', 'pwd','cat',
                        'lcd','lpwd', 'lls',
                        'quit', 'help']
    def __init__(self, host, session=None, davclient=None):
        self.sessions = {}
        self.host = host
        self.session = session
        self.davclient = davclient
        

    @property
    def  host(self):
        return self._host
    
    @host.setter
    def host(self, host):
        self._host = host
        self._hostname = host.replace("https:","").replace("/","")       

    @property
    def  hostname(self):
        return self._hostname
    
    @hostname.setter
    def hostname(self, hostname):
        self._host = "https://%s/" % (hostname)
        self._hostname = hostname 
    
    @property
    def session(self):
        return self._session
    
    @session.setter
    def session(self, session):
        self._session = session
        self.davclient = easywebdav.connect( self.hostname, protocol='https', session=session, path="cloud", verify_ssl=VERIFY_SSL)
        
    @property
    def PS1(self):
        if self.davclient is None:
            return "[!]> "
        return "%s:%s> " % (self.hostname, self.davclient.cwd)
    
    def get_host_session(self, host=None):
        #~ if host is None:
            #~ host = self.host
        #~ if not host.startswith("https"):
            #~ host = "https://%s/" % (host)
        #~ if host in self.sessions:
            #~ session = self.sessions[host]
        #~ else:
            #~ session = requests.Session()
            #~ self.sessions[host] = session
        #~ if not host == SERVER
            #~ session.params.update({'davguest':1})
        #~ return session
        
        if self.session is None:
            session = requests.Session()
            #session.params.update({'davguest':1})
        else:
            session = self.session
        session.params.update({'davguest': (not  host == SERVER) })
        return session
    
    def do(self, command, *args):
        if not command in self.commands:
            raise CommandNotFound("Unknow command '%s'" % command)
        
        cmd = getattr(self, "cmd_%s"%command, None)
        if cmd is None:
            cmd = getattr(self.davclient, command)
        
        return cmd(*args)
        
    def cmd_exists(self, *args):
        if (len(args)==0):
            return
        return self.davclient.exists(args[0])
    
    def cmd_mkdir(self, *args):
        if (len(args)==0):
            return
        return self.davclient.mkdir(args[0])

    def cmd_mkdirs(self, *args):
        if (len(args)==0):
            return
        return self.davclient.mkdirs(args[0])
 
    def cmd_rmdir(self, *args):
        if (len(args)==0):
            return
        return self.davclient.rmdir(args[0])        
        
    def cmd_delete(self, *args):
        if (len(args)==0):
            return
        return self.davclient.delete(args[0])        
        
    def cmd_upload(self, *args):
        if (len(args)==0):
            return
        args = list(args)
        if (len(args)==1):
            args.append(args[0])
            
        return self.davclient.upload(args[0], args[1])        

    def cmd_download(self, *args):
        if (len(args)==0):
            return
        args = list(args)
        if (len(args)==1):
            args.append(args[0])
            
        return self.davclient.download(args[0], args[1])        
        
    def cmd_host(self, *args):
        if (len(args)==0):
            return
        newhostname = args[0]
        newhost = "https://%s/" % newhostname
        if newhostname == "~" or newhost == SERVER:
            # bach to home server
            self.host = SERVER
            self.session = self.get_host_session(SERVER)
            return
        
        session_remote = self.get_host_session(newhost)
        session_home = self.get_host_session(SERVER)

        # call /magic on SERVER
        r = session_home.get( 
            SERVER + "magic",  
            params={'dest': newhost},
            allow_redirects=False,
            verify=VERIFY_SSL )
        
        if not 'location' in r.headers:
            raise Exception("Cannot start magic auth to '%s'" % newhostname)
        auth_url = r.headers['location']


        # call auth_url with "test" param
    
        r = session_remote.get( 
            auth_url,
            params={'test': 1 },
            verify=VERIFY_SSL )

        if r.json()['success']:
            self.hostname = newhostname
            self.session = session_remote
        else:
            raise Exception("Cannot magic auth to '%s'" % newhostname)
        

    def cmd_pwd(self, *args):
        return "%s%s" % ( self.davclient.baseurl, self.davclient.cwd )

    def cmd_ls(self, *args):
        extra_args = ["-a", "-l",  "-d"]
        
        show_hidden = "-a" in args
        show_list = "-l" in args
        show_only_dir = "-d" in args
        args = [ a for  a in args if not a in extra_args ]
        
        
        r = self.davclient.ls(*args)
        l = max([ len(str(f.size)) for f in r ] + [7,])
        
        def _fmt(type, size, name):
            if show_list:
                return "%s %*d %s" % (type, l, f.size , name)
            else:
                return name
        
        if show_hidden :
            print _fmt('d', 0, "./")
            if self.davclient.cwd!="/":
                print _fmt('d', 0, "../")
                
        for f in r:
            name = f.name.replace("/cloud"+self.davclient.cwd,"")
            type = "-"
            if name.endswith("/"):
                type = "d"
            if name!="":
                if show_hidden  or not name.startswith("."):
                    if not show_only_dir or  type=="d":
                        print _fmt(type, f.size , name)

    def cmd_lpwd(self, *args):
        return os.getcwd()

    def cmd_lcd(self, *args):
        if (len(args)==0):
            return
        os.chdir(args[0])
    
    def cmd_lls(self, *args):
        for f in os.listdir(os.getcwd()):
            if os.path.isdir(f):
                f=f+"/"
            print f
            
    def cmd_help(self, *args):
        print "ZotSH",__version__
        print 
        print "Commands:"
        for c in self.commands:
            print "\t",c
        print
        print "easywebdav", easywebdavversion.__version__, "(mod)"
        print "requests", requests.__version__

    def cmd_cat(self,*args):
        if (len(args)==0):
            return        
        rfile = args[0]
        resp = self.davclient._send('GET', rfile, (200,))
        print resp.text

def load_conf():
    global SERVER,USER,PASSWD,VERIFY_SSL
    homedir = os.getenv("HOME")
    if homedir is None:
        homedir = os.path.join(os.getenv("HOMEDRIVE"), os.getenv("HOMEPATH"))
    
    optsfile = ".zotshrc"
    if not os.path.isfile(optsfile):
        optsfile = os.path.join(homedir, ".zotshrc")
    
    if not os.path.isfile(optsfile):
        print "Please create a configuration file called '.zotshrc':"
        print "[zotsh]"
        print "host = https://yourhost.com/"
        print "username = your_username"
        print "password = your_password"
        sys.exit(-1)
    
    config = ConfigParser.ConfigParser()
    config.read(optsfile)
    SERVER = config.get('zotsh', 'host')
    USER = config.get('zotsh', 'username')
    PASSWD = config.get('zotsh', 'password')
    if config.has_option('zotsh', 'verify_ssl'):
        VERIFY_SSL = config.getboolean('zotsh', 'verify_ssl')


def zotsh():
    
    zotsh = ZotSH( SERVER)
    
    session_home = zotsh.get_host_session()

    #~ #login on home server
    print "loggin in..."
    r = session_home.get( 
        SERVER + "api/account/verify_credentials",  
        auth=HTTPBasicAuth(USER, PASSWD), 
        verify=VERIFY_SSL    )

    print "Hi", r.json()['name']

    zotsh.session = session_home

    # command loop
    input = raw_input(zotsh.PS1)
    while (input != "quit"):
        input = input.strip()
        if len(input)>0:
            toks = [ x.strip() for x in input.split(" ") ]
            
            command = toks[0]
            args = toks[1:]
            try:
                ret = zotsh.do(command, *args)
            except easywebdav.client.OperationFailed, e:
                print e
            except CommandNotFound, e:
                print e
            else:
                if ret is not None:
                    print ret
        

        input = raw_input(zotsh.PS1)


    

if __name__=="__main__":
    load_conf()
    zotsh()
    sys.exit()




