/* 
 * lib3DS - https://github.com/TazeTSchnitzel/lib3DS
 *
 * Code except where otherwise noted © 2013 Andrea Faulds.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
(function (lib3DS) {
    // *** INTERNAL VARIABLES ***
    var keyNames = {
        13: 'A',
        37: 'left',
        38: 'up',
        39: 'right',
        40: 'down'
    };

    // *** INTERNAL FUNCTIONS ***
    
    // Adds <meta name=viewport> tag to make 3DS browser zoom page correctly
    function makeMetaViewport(width) {
        var meta;
        meta = document.createElement('meta');
        meta.name = 'viewport';
        meta.content = 'width=' + width + ', initial-scale=1, user-scalable=no';
        document.head.appendChild(meta);
        return meta;
    }

    // Removes page padding to ensure correct screen alignment
    function removePadding() {
        document.body.style.padding = '0';
        document.body.style.margin = '0';
    }

    // Creates a fixed-size div for a particular screen
    function addScreen(width, height) {
        var screen;
        screen = document.createElement('div');
        screen.style.width = width + 'px';
        screen.style.height = height + 'px';
        screen.style.overflow = 'hidden';
        screen.style.position = 'relative';
        document.body.appendChild(screen);
        return screen;
    }

    // Makes browser constantly scroll to point, "bouncing back" if the arrow
    // keys (or thumb stick) moved the frame.
    function stickTo(x, y) {
        var interval;
        interval = window.setInterval(function () {
            window.scrollTo(x, y);
        }, 0);
        return interval
    }

    // *** PUBLIC API **

    // Sets up display mode and returns object containing one key:
    // "bottomScreen" - DOM element of div showing on bottom screen of 3DS
    // In this mode, there is no top screen. The bottom screen is 320x212px.
    // No keys cause bouncing by being pressed in this mode.
    lib3DS.initMode320 = function () {
        var meta, bottomScreen;
        
        removePadding();
        makeMetaViewport(320);
        bottomScreen = addScreen(320, 212);
        stickTo(0, 0);
        
        return {
            bottomScreen: bottomScreen
        };
    };

    // Sets up display mode and returns object containing two keys:
    // "topScreen" - DOM element of div showing on top screen of 3DS
    // "bottomScreen" - DOM element of div showing on bottom screen of 3DS
    // In this mode, top screen is 320x214px and the bottom screen is 320x212px.
    // Pressing the up key may cause bouncing.
    lib3DS.initModeDual320 = function () {
        var meta, topScreen, bottomScreen;
        
        removePadding();
        makeMetaViewport(320);
        topScreen = addScreen(320, 214);
        bottomScreen = addScreen(320, 212);
        stickTo(0, 214);
        
        return {
            topScreen: topScreen,
            bottomScreen: bottomScreen
        };
    };

    // Registers event handlers for buttons. onkeydown and onkeyup will be
    // called when a button is depressed or released, respectively. The name of
    // the button ("A", "left", "up", "right" or "down") will be passed as the
    // first parameter when calling them. Both parameters are optional and if
    // a falsey value (null, false, undefined, etc.) is passed instead, the
    // function will not be called.
    lib3DS.handleButtons = function (onkeydown, onkeyup) {
        if (onkeydown) {
            document.body.onkeydown = function (e) {
                if (keyNames.hasOwnProperty(e.which)) {
                    e.preventDefault();
                    onkeydown(keyNames[e.which]);
                    return false;
                }
            };
        }
        if (onkeyup) {
            document.body.onkeyup = function (e) {
                if (keyNames.hasOwnProperty(e.which)) {
                    e.preventDefault();
                    onkeyup(keyNames[e.which]);
                    return false;
                }
            };
        }
    };
}(window.lib3DS = window.lib3DS || {}));
