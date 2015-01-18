[size=large][b]Frequently Asked Questions For Developers[/b][/size]

[toc]


[h3]What does $a mean?[/h3]
$a is a class defined in boot.php and passed all around Red Matrix as a global reference variable. It defines everything necessary for the Red Matrix application: Server variables, URL arguments, page structures, layouts, content, installed plugins, output device info, theme info, identity of the observer and (potential) page owner ... 
We don't ever create more than one instance and always modify the elements of the single instance. The mechanics of this are somewhat tricky. If you have a function that is passed $a and needs to modify $a you need to declare it as a reference with '&' e.g. 

[code]function foo(&$a) { $a->something = 'x'; // whatever };

*or* access it within your function  as a global variable via get_app()

function foo() {
    $a = get_app();
    $a->something = 'x';
}


function foo($a) { $a->something = 'x'; }; 

will *not* change the global app state. 

function foo() {
   get_app()->something = 'x';
}
[/code]



#include doc/macros/main_footer.bb;

