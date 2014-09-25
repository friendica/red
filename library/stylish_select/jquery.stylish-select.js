/**
* Stylish Select 0.4.9 - jQuery plugin to replace a select drop down box with a stylable unordered list
* http://github.com/scottdarby/Stylish-Select
*
* Requires: jQuery 1.3 or newer
*
* Contributions from Justin Beasley: http://www.harvest.org/ 
* Anatoly Ressin: http://www.artazor.lv/ Wilfred Hughes: https://github.com/Wilfred
* Grigory Zarubin: https://github.com/Craigy-
*
* Dual licensed under the MIT and GPL licenses.
*/
(function($){
    //add class to html tag
    $('html').addClass('stylish-select');

    //Cross-browser implementation of indexOf from MDN: https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array/indexOf
    if (!Array.prototype.indexOf){
        Array.prototype.indexOf = function(searchElement /*, fromIndex */){
            if (this === void 0 || this === null)
                throw new TypeError();

            var t = Object(this);
            var len = t.length >>> 0;
            if (len === 0)
                return -1;

            var n = 0;
            if (arguments.length > 0){
                n = Number(arguments[1]);
                if (n !== n) // shortcut for verifying if it's NaN
                    n = 0;
                else if (n !== 0 && n !== (1 / 0) && n !== -(1 / 0))
                    n = (n > 0 || -1) * Math.floor(Math.abs(n));
            }

            if (n >= len)
                return -1;

            var k = n >= 0
            ? n
            : Math.max(len - Math.abs(n), 0);

            for (; k < len; k++){
                if (k in t && t[k] === searchElement)
                    return k;
            }
            return -1;
        };
    }

    //utility methods
    $.fn.extend({
        getSetSSValue: function(value){
            if (value){
                //set value and trigger change event
                $(this).val(value).change();
                return this;
            } else {
                return $(this).find(':selected').val();
            }
        },
        //added by Justin Beasley
        resetSS: function(){
            var oldOpts = $(this).data('ssOpts');
                $this = $(this);
                $this.next().remove();
                //unbind all events and redraw
                $this.unbind('.sSelect').sSelect(oldOpts);
        }
    });

    $.fn.sSelect = function(options){
        return this.each(function(){
            var defaults = {
                defaultText:    'Please select',
                animationSpeed: 0, //set speed of dropdown
                ddMaxHeight:    '', //set css max-height value of dropdown
                containerClass: '' //additional classes for container div
            };

            //initial variables
            var opts = $.extend(defaults, options),
                $input = $(this),
                $containerDivText    = $('<div class="selectedTxt"></div>'),
                $containerDiv        = $('<div class="newListSelected ' + opts.containerClass + ($input.is(':disabled') ? ' newListDisabled' : '') + '"></div>'),
                $containerDivWrapper = $('<div class="SSContainerDivWrapper" style="visibility:hidden;"></div>'),
                $newUl               = $('<ul class="newList"></ul>'),
                currentIndex         = -1,
                prevIndex            = -1,
                keys                 = [],
                prevKey              = false,
                prevented            = false,
                $newLi;

            //added by Justin Beasley
            $(this).data('ssOpts',options);

            if( $(this).next('.newListSelected').length ) {
                return;
            }

            //build new list
            $containerDiv.insertAfter($input);
            $containerDiv.attr("tabindex", $input.attr("tabindex") || "0");
            $containerDivText.prependTo($containerDiv);
            $newUl.appendTo($containerDiv);
            $newUl.wrap($containerDivWrapper);
            $containerDivWrapper = $newUl.parent();
            $input.hide();

            if($input.is(':disabled')){
                return;
            }

            //added by Justin Beasley (used for lists initialized while hidden)
            $containerDivText.data('ssReRender',!$containerDivText.is(':visible'));

            //add one item to list
            function addItem(item, container) {
                var option = $(item).text(),
                    key = $(item).val(),
                    isDisabled = $(item).is(':disabled');

                if (!isDisabled && !$(item).parents().is(':disabled')) {
                    //add first letter of each word to array
                    keys.push(option.charAt(0).toLowerCase());
                }
                container.append($('<li><a'+(isDisabled ? ' class="newListItemDisabled"' : '')+' href="JavaScript:void(0);">'+option+'</a></li>').data({
                    'key' : key,
                    'selected' : $(item).is(':selected')
                }));
            }

            $input.children().each(function(){
                if ($(this).is('option')){
                    addItem(this, $newUl);
                } else {
                    var optionTitle = $(this).attr('label'),
                        $optGroup = $('<li class="newListOptionTitle ' + ($(this).is(':disabled') ? 'newListOptionDisabled' : '') + '">'+optionTitle+'</li>'),
                        $optGroupList = $('<ul></ul>');

                    $optGroup.appendTo($newUl);
                    $optGroupList.appendTo($optGroup);

                    $(this).children().each(function(){
                        addItem(this, $optGroupList);
                    });
                }
            });

            //cache list items object
            $newLi = $newUl.find('li a:not(.newListItemDisabled)').not(function(){
                return $(this).parents().hasClass('newListOptionDisabled');
            });

            //get selected item from new list (because it doesn't contain disabled options)
            $newLi.each(function(i){
                if ($(this).parent().data('selected')){
                    opts.defaultText = $(this).html();
                    currentIndex = prevIndex = i;
                }
            });

            //get heights of new elements for use later
            var newUlHeight = $newUl.height(),
                containerHeight = $containerDiv.height(),
                newLiLength     = $newLi.length;

            //check if a value is selected
            if (currentIndex != -1){
                navigateList(currentIndex);
            } else {
                //set placeholder text
                $containerDivText.text(opts.defaultText);
            }

            //decide if to place the new list above or below the drop-down
            function newUlPos(){
                var containerPosY = $containerDiv.offset().top,
                    docHeight     = $(window).height(),
                    scrollTop     = $(window).scrollTop();

                //if height of list is greater then max height, set list height to max height value
                if (newUlHeight > parseInt(opts.ddMaxHeight)){
                    newUlHeight = parseInt(opts.ddMaxHeight);
                }

                containerPosY = containerPosY-scrollTop;
                if (containerPosY+newUlHeight >= docHeight){
                    $newUl.css({
                        height: newUlHeight
                    });
                    $containerDivWrapper.css({
                        top:    '-'+newUlHeight+'px',
                        height: newUlHeight
                    });
                    $input.onTop = true;
                } else {
                    $newUl.css({
                        height: newUlHeight
                    });
                    $containerDivWrapper.css({
                        top:     containerHeight+'px',
                        height: newUlHeight
                    });
                    $input.onTop = false;
                }
            }

            //run function on page load
            newUlPos();

            //run function on browser window resize
            $(window).bind('resize.sSelect scroll.sSelect', newUlPos);

            //positioning
            function positionFix(){
                $containerDiv.css('position','relative');
            }

            function positionHideFix(){
                $containerDiv.css(
                {
                    position: 'static'
                });
            }

            $containerDivText.bind('click.sSelect',function(event){
                event.stopPropagation();

                //added by Justin Beasley
                if($(this).data('ssReRender')){
                    newUlHeight = $newUl.height('').height();
                    $containerDivWrapper.height('');
                    containerHeight = $containerDiv.height();
                    $(this).data('ssReRender',false);
                    newUlPos();
                }

                //hide all menus apart from this one
                $('.SSContainerDivWrapper')
                .not($(this).next())
                .hide()
                .parent()
                .css('position', 'static')
                .removeClass('newListSelFocus');

                //show/hide this menu
                $containerDivWrapper.toggle();
                positionFix();

                //scroll list to selected item
                if(currentIndex == -1) currentIndex = 0;
                try {
                    $newLi.eq(currentIndex).focus();
                } catch(ex) {}
            });

            function closeDropDown(fireChange, resetText){
                if(fireChange == true){
                    prevIndex = currentIndex;
                    $input.change();
                }

                if(resetText == true){
                    currentIndex = prevIndex;
                    navigateList(currentIndex);
                }

                $containerDivWrapper.hide();
                positionHideFix();
            }

            $newLi.bind('click.sSelect',function(e){
                var $clickedLi = $(e.target);

                //update counter
                currentIndex = $newLi.index($clickedLi);

                //remove all hilites, then add hilite to selected item
                prevented = true;
                navigateList(currentIndex, true);
                closeDropDown();
            });

            $newLi.bind('mouseenter.sSelect',
                function(e){
                    var $hoveredLi = $(e.target);
                    $hoveredLi.addClass('newListHover');
                }).bind('mouseleave.sSelect',
                function(e){
                    var $hoveredLi = $(e.target);
                    $hoveredLi.removeClass('newListHover');
                });

            function navigateList(currentIndex, fireChange){
                if(currentIndex == -1){
                    $containerDivText.text(opts.defaultText);
                    $newLi.removeClass('hiLite');
                } else {
                    $newLi.removeClass('hiLite')
                    .eq(currentIndex)
                    .addClass('hiLite');

                    var text = $newLi.eq(currentIndex).text(),
                        val = $newLi.eq(currentIndex).parent().data('key');

                    try {
                        $input.val(val);
                    } catch(ex) {
                        // handle ie6 exception
                        $input[0].selectedIndex = currentIndex;
                    }

                    $containerDivText.text(text);

                    //only fire change event if specified
                    if(fireChange == true){
                        prevIndex = currentIndex;
                        $input.change();
                    }

                    if ($containerDivWrapper.is(':visible')){
                        try {
                            $newLi.eq(currentIndex).focus();
                        } catch(ex) {}
                    }
                }
            }

            $input.bind('change.sSelect',function(event){
                var $targetInput = $(event.target);
                //stop change function from firing
                if (prevented == true){
                    prevented = false;
                    return false;
                }
                var $currentOpt  = $targetInput.find(':selected');
                    currentIndex = $targetInput.find('option').index($currentOpt);
                    navigateList(currentIndex);
            });

            //handle up and down keys
            function keyPress(element){
                //when keys are pressed
                $(element).unbind('keydown.sSelect').bind('keydown.sSelect',function(e){
                    var keycode = e.which;

                    //prevent change function from firing
                    prevented = true;

                    switch(keycode){
                        case 40: //down
                        case 39: //right
                            incrementList();
                            return false;
                            break;
                        case 38: //up
                        case 37: //left
                            decrementList();
                            return false;
                            break;
                        case 33: //page up
                        case 36: //home
                            gotoFirst();
                            return false;
                            break;
                        case 34: //page down
                        case 35: //end
                            gotoLast();
                            return false;
                            break;
                        case 13: //enter
                        case 27: //esc
                            closeDropDown(true);
                            return false;
                            break;
                        case 9: //tab
                            closeDropDown(true);
                            nextFormElement();
                            return false;
                            break;
                    }

                    //check for keyboard shortcuts
                    keyPressed = String.fromCharCode(keycode).toLowerCase();

                    var currentKeyIndex = keys.indexOf(keyPressed);

                    if (typeof currentKeyIndex != 'undefined'){ //if key code found in array
                        ++currentIndex;
                        currentIndex = keys.indexOf(keyPressed, currentIndex); //search array from current index

                        if (currentIndex == -1 || currentIndex == null || prevKey != keyPressed){
                            // if no entry was found or new key pressed search from start of array
                            currentIndex = keys.indexOf(keyPressed);
                        }

                        navigateList(currentIndex);
                        //store last key pressed
                        prevKey = keyPressed;
                        return false;
                    }
                });
            }

            function incrementList(){
                if (currentIndex < (newLiLength-1)){
                    ++currentIndex;
                    navigateList(currentIndex);
                }
            }

            function decrementList(){
                if (currentIndex > 0){
                    --currentIndex;
                    navigateList(currentIndex);
                }
            }

            function gotoFirst(){
                currentIndex = 0;
                navigateList(currentIndex);
            }

            function gotoLast(){
                currentIndex = newLiLength-1;
                navigateList(currentIndex);
            }

            $containerDiv.bind('click.sSelect',function(e){
                e.stopPropagation();
                keyPress(this);
            });

            $containerDiv.bind('focus.sSelect',function(){
                $(this).addClass('newListSelFocus');
                keyPress(this);
            });

            $containerDiv.bind('blur.sSelect',function(){
                $(this).removeClass('newListSelFocus');
            });

            //hide list on blur
            $(document).bind('click.sSelect',function(){
                $containerDiv.removeClass('newListSelFocus');
                if ($containerDivWrapper.is(':visible')){
                    closeDropDown(false, true);
                } else {
                    closeDropDown(false);
                }
            });

            //select next form element in document
            function nextFormElement() {
                var fields = $('body').find('button,input,textarea,select'),
                    index = fields.index($input);
                if (index > -1 && (index + 1) < fields.length) {
                    fields.eq(index + 1).focus();
                }
                return false;
            }
            // handle focus on original select element
            $input.focus(function(){
                $input.next().focus();
            });

            //add classes on hover
            $containerDivText.bind('mouseenter.sSelect',
                function(e){
                    var $hoveredTxt = $(e.target);
                    $hoveredTxt.parent().addClass('newListSelHover');
                }).bind('mouseleave.sSelect',
                function(e){
                    var $hoveredTxt = $(e.target);
                    $hoveredTxt.parent().removeClass('newListSelHover');
                });

            //reset left property and hide
            $containerDivWrapper.css({
                left: '0',
                display: 'none',
                visibility: 'visible'
            });

        });

    };

})(jQuery);