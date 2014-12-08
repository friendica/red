Advanced Directory Search
=========================


Advanced Directory Search is enabled in "Expert Mode" from your Settings => Additional features page.

On the Directory page an option named "Advanced" will apear in the "Find Channels" widget (typically in the sidebar). Clicking "Advanced" will open another search box for entering advanced search requests.

Advanced requests include

* name=xxx 
[Channel name contains xxx]

* address=xxx
[Channel address (webbie) contains xxx]

* locale=xxx
[Locale (typically 'city') contains xxx]

* region=xxx
[Region (state/territory) contains xxx]

* postcode=xxx
[Postcode or zip code contains xxx]

* country=xxx
[Country name contains xxx]

* gender=xxx
[Gender contains xxx]

* marital=xxx
[Marital status contains xxx]

* sexual=xxx
[Sexual preference contains xxx]

* keywords=xxx
[Keywords contain xxx]

There are many reasons why a match may not return what you're looking for, as many channels do not provide detailed information in their default (public) profile, and many of these fields allow free-text input in several languages - and this may be difficult to match precisely. For instance you may have better results finding somebody in the USA with 'country=u' (along with some odd channels from Deutschland and Bulgaria and Australia) because this could be represented in a profile as US, U.S.A, USA, United States, etc...

Future revisions of this tool may try to smooth over some of these difficulties. 

Requests may be joined together with 'and', 'or', and 'and not'. 

Terms containing spaces must be quoted.

Example:
    
    name="charlie brown" and country=canada and not gender=female

#include doc/macros/main_footer.bb;
