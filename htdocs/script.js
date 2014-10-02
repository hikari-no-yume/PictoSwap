(function (PictoSwap) {
    'use strict';

    // Calculates a distance
    function calcDistance(x1, y1, x2, y2) {
        return Math.sqrt(((x2 - x1) * (x2 - x1)) + ((y2 - y1) * (y2 - y1)));
    }

    function compose(context, SID) {
        var inkMeter = new PictoSwap.InkMeter(20000, 20000),
            colourPicker = new PictoSwap.ColourPicker(),
            canvas = new PictoSwap.Canvas(308, 168),
            previewCanvas = new PictoSwap.Canvas(308, 168);

        context.topScreen.innerHTML = context.bottomScreen.innerHTML = '';

        var previewArea, previewNote, previewFrame;
        previewArea = $({
            parentElement: context.topScreen,
            tagName: 'div',
            id: 'preview-area',
            children: [
                previewNote = $({
                    tagName: 'div',
                    className: 'preview-note',
                    children: [
                        $('Press (A) to play, touch and hold to draw')
                    ]
                }),
                $({
                    tagName: 'div',
                    className: 'canvas-box',
                    children: [
                        previewFrame = $({
                            tagName: 'div',
                            className: 'canvas-frame',
                            children: [
                                previewCanvas.element
                            ]
                        })
                    ]
                }),
                $({
                    tagName: 'div',
                    className: 'preview-date',
                    children: [
                        $((new Date()).toDateString())
                    ]
                })
            ]
        });

        var saveButton, colourButton, drawingArea, canvasFrame, pageCounter, eraserButton, downButton, upButton;
        drawingArea = $({
            parentElement: context.bottomScreen,
            tagName: 'div',
            id: 'drawing-area',
            children: [
                $({
                    tagName: 'div',
                    className: 'canvas-box',
                    children: [
                        canvasFrame = $({
                            tagName: 'div',
                            className: 'canvas-frame',
                            children: [
                                canvas.element
                            ]
                        })
                    ]
                }),
                $({
                    tagName: 'div',
                    id: 'tool-bar',
                    children: [
                        saveButton = $({
                            tagName: 'button',
                            id: 'save-button',
                            children: [
                                $('Save')
                            ]
                        }),
                        $({
                            tagName: 'div',
                            id: 'colour-box',
                            children: [
                                colourButton = $({
                                    tagName: 'button',
                                    id: 'colour-button'
                                })
                            ],
                        }),
                        $({
                            tagName: 'div',
                            id: 'tool-box',
                            children: [
                                $({
                                    tagName: 'button',
                                    id: 'pencil-button'
                                }),
                                inkMeter.element,
                                eraserButton = $({
                                    tagName: 'button',
                                    id: 'eraser-button'
                                })
                            ]
                        }),
                        pageCounter = $({
                            tagName: 'div',
                            id: 'page-count',
                            children: [
                                $('p. 1/4')
                            ]
                        }),
                        upButton = $({
                            tagName: 'button',
                            id: 'up-button'
                        }),
                        downButton = $({
                            tagName: 'button',
                            id: 'down-button'
                        })
                    ]
                }),
                colourPicker.element
            ]
        });

        var pages = [[], [], [], []], pageInkUsage = [0, 0, 0, 0],
            page = 0, pageCount = 4, inkUsage = 0;
        var pageBackground = 'green-letter.png';
        var drawing = false, drawColour = 'black', lastX = 0, lastY = 0;

        previewFrame.style.backgroundImage = 'url(backgrounds/' + pageBackground + ')';
        canvasFrame.style.backgroundImage = 'url(backgrounds/' + pageBackground + ')';

        canvas.element.onmousedown = function (e) {
            // Only allow drawing if we have enough ink
            if (!inkMeter.subtractInk(1)) {
                return;
            }

            // Amount of ink this page uses
            inkUsage += 1;

            // Begin a stroke
            drawing = true;
            canvas.beginStroke();
            previewCanvas.beginStroke();

            // Draw dot
            canvas.addDot(drawColour, e.layerX, e.layerY);
            previewCanvas.addDot(drawColour, e.layerX, e.layerY);

            // Store the position at present so that we know where next
            // segment will start
            lastX = e.layerX;
            lastY = e.layerY;
        };
        var onMove = canvas.element.onmousemove = function (e) {
            // Prevent mouse moving causing drawing on desktop
            // (Obviously, "mousemove" can't fire when not dragging on 3DS)
            if (drawing && (lastX !== e.layerX || lastY !== e.layerY)) {
                // The length of this segment will be subtracted from our "ink"
                var inkUsed = calcDistance(lastX, lastY, e.layerX, e.layerY);

                // Only allow drawing if we have enough ink
                if (!inkMeter.subtractInk(inkUsed)) {
                    return;
                }

                // Amount of ink this page uses
                inkUsage += inkUsed;

                // Draw line
                canvas.addLine(drawColour, lastX, lastY, e.layerX, e.layerY);
                previewCanvas.addLine(drawColour, lastX, lastY, e.layerX, e.layerY);
            }

            lastX = e.layerX;
            lastY = e.layerY;
        };
        canvas.element.onmouseup = function (e) {
            if (drawing) {
                onMove(e);

                // End of stroke
                canvas.endStroke();
                previewCanvas.endStroke();
                
                drawing = false;
            }
        };

        function serialiseLetter() {
            return {
                background: pageBackground,
                pages: pages,
                pageInkUsage: pageInkUsage
            };
        }

        saveButton.onclick = function () {
            savePage();
            loading(context, 'Saving letter...');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/api.php?' + SID);
            
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var data = JSON.parse(xhr.responseText);
                        if (data.error) {
                            alert(data.error);
                        } else {
                            loadLetters(context, SID);
                        }
                    } else {
                        alert("Error! Request returned " + xhr.status + "!");
                    }
                }
            };
            xhr.send(JSON.stringify({
                action: 'new_letter',
                letter: serialiseLetter()
            }));
        };

        colourButton.onclick = function () {
            colourPicker.open(function (colour) {
                colourButton.style.backgroundColor = colour;
                drawColour = colour;
            });
        };

        eraserButton.onclick = function () {
            if (confirm("Clear this page?")) {
                canvas.clearStrokes();
                previewCanvas.clearStrokes();

                // Reset ink usage for this page
                inkMeter.addInk(inkUsage);
                inkUsage = 0;
            }
        };

        function savePage() {
            pages[page] = canvas.exportStrokes();
            pageInkUsage[page] = inkUsage;
        }

        function loadPage() {
            canvas.importStrokes(pages[page], true);
            inkUsage = pageInkUsage[page];
            previewCanvas.importStrokes(pages[page], true);
        }

        function updatePageCounter() {
            pageCounter.innerHTML = '';
            pageCounter.appendChild($('p. ' + (page + 1) + '/' + pageCount));
        }

        downButton.onclick = function () {
            // Limit no. of pages
            if (page + 1 < pageCount) {
                savePage();
                page++;
                updatePageCounter()
                loadPage();
            }   
        };

        upButton.onclick = function () {
            if (page - 1 >= 0) {
                savePage();
                page--;
                updatePageCounter()
                loadPage();
            }   
        };

        var cancelFunction = null;
        lib3DS.handleButtons(function (key) {
            if (key === 'A') {
                if (cancelFunction) {
                    cancelFunction();
                    cancelFunction = null;
                    previewNote.innerHTML = '';
                    previewNote.appendChild($('Press (A) to play, touch and hold to draw'));

                    // Replay again (but instantly) so that preview is visible
                    previewCanvas.replay(true);
                } else {
                    cancelFunction = previewCanvas.replay(false, function () {
                        previewNote.innerHTML = '';
                        previewNote.appendChild($('Press (A) to play, touch and hold to draw'));
                        cancelFunction = null;
                    });
                    previewNote.innerHTML = '';
                    previewNote.appendChild($('Press (A) to stop, touch and hold to draw'));
                }
            }
        });
    }

    // Displays loading screen
    function loading(context, text) {
        $({
            parentElement: context.bottomScreen,
            tagName: 'div',
            className: 'loading-background',
            children: [
                $({
                    tagName: 'div',
                    className: 'loading',
                    children: [
                        $({
                            tagName: 'div',
                            className: 'loading-text',
                            children: [
                                $(text)
                            ]
                        }),
                        $({
                            tagName: 'img',
                            src: 'res/spinner.gif'
                        })
                    ]
                })
            ]
        });
    }

    // Views letter
    function viewLetter(context, letter) {
        var content = letter.content;

        var previewCanvas = new PictoSwap.Canvas(308, 168);
        var previewArea, previewNote, previewFrame;
        context.topScreen.innerHTML = '';
        previewArea = $({
            parentElement: context.topScreen,
            tagName: 'div',
            id: 'preview-area',
            children: [
                previewNote = $({
                    tagName: 'div',
                    className: 'preview-note',
                    children: [
                        $('Press (A) to stop')
                    ]
                }),
                $({
                    tagName: 'div',
                    className: 'canvas-box',
                    children: [
                        previewFrame = $({
                            tagName: 'div',
                            className: 'canvas-frame',
                            style: {
                                backgroundImage: 'url(backgrounds/' + content.background + ')'
                            },
                            children: [
                                previewCanvas.element
                            ]
                        })
                    ]
                }),
                $({
                    tagName: 'div',
                    className: 'preview-date',
                    children: [
                        $(letter.timestamp)
                    ]
                })
            ]
        });

        previewCanvas.importStrokes(content.pages[0]);
        previewCanvas.replay(false);
    }

    // Loads letter for letter view screen
    function loadLetter(context, letterID, SID) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/api.php?action=letter&id=' + letterID + '&' + SID);
        
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    if (data.error) {
                        alert(data.error);
                    } else {
                        viewLetter(context, data.letter);
                    }
                } else {
                    alert("Error! Request returned " + xhr.status + "!");
                }
            }
        };
        xhr.send();
    }

    // Displays letter browsing screen
    function browse(context, letters, SID) {
        context.topScreen.innerHTML = context.bottomScreen.innerHTML = '';
        $({
            parentElement: context.topScreen,
            tagName: 'div',
            id: 'preview-area'
        });

        var composeButton, letterCarousel, leftButton, rightButton;
        $({
            parentElement: context.bottomScreen,
            tagName: 'div',
            id: 'browse-area',
            children: [
                letterCarousel = $({
                    tagName: 'div',
                    id: 'letter-carousel'
                }),
                leftButton = $({
                    tagName: 'button',
                    id: 'carousel-left-button'
                }),
                rightButton = $({
                    tagName: 'button',
                    id: 'carousel-right-button'
                }),
                composeButton = $({
                    tagName: 'button',
                    id: 'compose-button',
                    children: [
                        $({
                            tagName: 'img',
                            src: 'res/compose.png'
                        }),
                        $('Write Letter')
                    ],
                })
            ]
        });

        var LETTER_GAP = 180, letterElements = [], selected = 0, x = 0;
        letters.forEach(function (letter, i) {
            var elem = $({
                tagName: 'img',
                src: 'previews/' + letter.letter_id + '-0.png',
                className: 'letter-preview',
                parentElement: letterCarousel,
                style: {
                    left: x + 'px'
                }
            });
            if (letter.own) {
                elem.className += ' letter-preview-own';
            }
            elem.onclick = function () {
                updateCarousel(i);
                loadLetter(context, letter.letter_id, SID);
            };
            letterElements.push(elem);
            x += LETTER_GAP;
        });
        letterCarousel.style.width = x + 'px';
        
        function updateCarousel(newSelected) {
            selected = newSelected;
            letterCarousel.style.marginLeft = selected * -LETTER_GAP + 'px';
        }

        composeButton.onclick = function () {
            compose(context, SID);
        };

        var goLeft = leftButton.onclick = function () {
            if (selected - 1 >= 0) {
                updateCarousel(selected - 1);
            }
        }, goRight = rightButton.onclick = function () {
            if (selected + 1 < letters.length) {
                updateCarousel(selected + 1);
            }
        };

        lib3DS.handleButtons(function (key) {
            if (key === 'left') {
                goLeft();
            } else if (key === 'right') {
                goRight();
            } else if (key === 'A') {
                letterElements[selected].onclick();
            }
        });

        updateCarousel(0);
    }

    // Makes request for letters then switches to letter browsing screen when done
    function loadLetters(context, SID) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/api.php?action=letters&' + SID);
        
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    if (data.error) {
                        alert(data.error);
                    } else {
                        browse(context, data.letters, SID);
                    }
                } else {
                    alert("Error! Request returned " + xhr.status + "!");
                }
            }
        };
        xhr.send();

        loading(context, 'Loading letters...');
    }

    // Displays login screen
    function login(context) {
        context.topScreen.innerHTML = context.bottomScreen.innerHTML = '';
        $({
            parentElement: context.topScreen,
            tagName: 'div',
            id: 'preview-area',
            children: [
                $({
                    tagName: 'h1',
                    children: [
                        $('PictoSwap')
                    ]
                }),
                $({
                    tagName: 'p',
                    children: [
                        $('With PictoSwap, you can draw messages and send them to your friends on other 3DS systems!')
                    ]
                })
            ]
        });

        var username, password, loginBtn, registerBtn;

        $({
            parentElement: context.bottomScreen,
            tagName: 'div',
            id: 'login-area',
            children: [
                username = $({
                    tagName: 'input',
                    type: 'text',
                    placeholder: 'username',
                    id: 'username-input'
                }),
                password = $({
                    tagName: 'input',
                    type: 'password',
                    placeholder: 'password',
                    id: 'password-input'
                }),
                loginBtn = $({
                    tagName: 'button',
                    id: 'login-button',
                    children: [
                        $('Log in')
                    ]
                }),
                registerBtn = $({
                    tagName: 'button',
                    id: 'register-button',
                    children: [
                        $('Register')
                    ]
                })
            ]
        });

        registerBtn.onclick = function () {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/api.php');
            
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var data = JSON.parse(xhr.responseText);
                        if (data.error) {
                            alert(data.error);
                        } else {
                            alert("Registration successful. Now try to log in.");
                        }
                    } else {
                        alert("Error! Request returned " + xhr.status + "!");
                    }
                }
            };
            xhr.send(JSON.stringify({
                action: 'register',
                username: username.value,
                password: password.value
            }));
        };

        loginBtn.onclick = function () {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/api.php');
            
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var data = JSON.parse(xhr.responseText);
                        if (data.error) {
                            alert(data.error);
                        } else {
                            loadLetters(context, data.SID);
                        }
                    } else {
                        alert("Error! Request returned " + xhr.status + "!");
                    }
                }
            };
            xhr.send(JSON.stringify({
                action: 'login',
                username: username.value,
                password: password.value
            }));
        };
    }

    window.onerror = alert;
    window.onload = function () {
        var context = lib3DS.initModeDual320();
        var data = PictoSwap.userData;
        if (data.logged_in) {
            loadLetters(context, data.SID);
        } else {
            login(context);
        }
    };
}(window.PictoSwap = window.PictoSwap || {}));
