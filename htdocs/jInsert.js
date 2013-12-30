/* 
 * jsInsert v1.0 - https://github.com/TazeTSchnitzel/jsInsert
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

function $(props) {
    if (typeof props === 'string') {
        return document.createTextNode(props);
    }

    var elem = document.createElement(props.tagName);
    for (var name in props) {
        if (props.hasOwnProperty(name)) {
            if (name === 'style') {
                for (var styleName in props.style) {
                    if (props.style.hasOwnProperty(styleName)) {
                        elem.style[styleName] = props.style[styleName];
                    }
                }
            } else if (name !== 'tagName' && name !== 'parentElement' && name !== 'children') {
                elem[name] = props[name];
            }
        }
    }

    if (props.hasOwnProperty('children')) {
        props.children.forEach(function (child) {
            elem.appendChild(child); 
        });
    }

    if (props.hasOwnProperty('parentElement')) {
        props.parentElement.appendChild(elem);
    }

    return elem;
}
