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
            previewCanvas = new PictoSwap.Canvas(308, 168),
            pencilTool = new PictoSwap.PencilTool(canvas,previewCanvas),

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
                                $('Exit')
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

	var pages = [], currentPage=0, pageCount=4;
	for(var i=0;i<pageCount;i++){
            pages.push(new PictoSwap.Page());
        }


        var pageBackground = 'green-letter.png';

        previewFrame.style.backgroundImage = 'url(backgrounds/' + pageBackground + ')';
        canvasFrame.style.backgroundImage = 'url(backgrounds/' + pageBackground + ')';


        currentTool = pencilTool; //Set the current tool to the pencil tool

        canvas.element.onmousedown = function (e) {
            // Only allow drawing if we have enough ink
            if (!inkMeter.subtractInk(1)) {
                return;
            }

            // Amount of ink this page uses
            pages[currentPage].inkUsage += 1;

            if (pages[currentPage].empty) {
                pages[currentPage].empty = false;
                saveButton.innerHTML = 'Save';
            }


            currentTool.onCanvasMousedown(e,pages[currentPage]);

        };
        canvas.element.onmousemove = function (e) {
            currentTool.onCanvasMousemove(e,pages[currentPage]);
        };
        canvas.element.onmouseup = function (e) {
            currentTool.onCanvasMouseup(e,pages[currentPage]);
        };

        function serialiseLetter() {
            var pageDataArray = [], pageInkArray = [];
            for(var i=0;i<pageCount;i++){
            pageDataArray.push(pages[i].data);
            pageInkArray.push(pages[i].inkUsage);
	    }
            return {
                background: pageBackground,
                pages: pageDataArray,
                pageInkUsage: pageInkArray
            };
        }

        saveButton.onclick = function () {
            // If pages empty, saveButton is actually exitButton
            var allempty = true;
            for(var i=0;i<pages.length;i++){
                if(!pages[i].empty)allempty=false;
            }
            if (allempty) {
                loadLetters(context, SID);
                return;
            }

            savePage();
            loading(context.bottomScreen, 'Saving letter...');

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
                inkMeter.addInk(pages[currentPage].inkUsage);
                pages[currentPage].inkUsage = 0;

                // Check total ink usage now
                var notEmpty = false;
                pages.forEach(function (page, i) {
                    if (page.inkUsage && i !== currentPage) {
                        notEmpty = true;
                    }
                });

                if (!notEmpty) {
                    saveButton.innerHTML = 'Exit';
                }
            }
        };

        function savePage() {
            pages[currentPage].data = canvas.exportStrokes();
        }

        function loadPage() {
            canvas.importStrokes(pages[currentPage].data, true);
            previewCanvas.importStrokes(pages[currentPage].data, true);
        }

        function updatePageCounter() {
            pageCounter.innerHTML = '';
            pageCounter.appendChild($('p. ' + (page + 1) + '/' + pageCount));
        }

        downButton.onclick = function () {
            // Limit no. of pages
            if (currentPage + 1 < pageCount) {
                savePage();
                currentPage++;
                updatePageCounter()
                loadPage();
            }   
        };

        upButton.onclick = function () {
            if (currentPage - 1 >= 0) {
                savePage();
                currentPage--;
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
    function loading(parentElement, text) {
        return $({
            parentElement: parentElement,
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

        var pageCount = 0;
        content.pages.forEach(function (page) {
            if (page.length) {
                pageCount++;
            }
        });

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
                        $('Page 1/' + pageCount)
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

        (function playPage(pageNo) {
            if (pageNo >= pageCount) {
                return;
            }
            previewNote.innerHTML = 'Page ' + (pageNo + 1) + '/' + pageCount;
            previewCanvas.importStrokes(content.pages[pageNo]);
            previewCanvas.replay(false, function () {
                setTimeout(function () {
                    playPage(pageNo + 1);
                }, 1000);
            });
        }(0));
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

        loading(context.topScreen, 'Loading letter...');
    }

    // Makes friend requests popup
    function makeFriendRequests(SID) {
        var friendRequests, logoutButton, friendRequestList, addFriendBox, addFriendButton;
        friendRequests = $({
            tagName: 'div',
            id: 'friend-requests',
            className: 'hidden',
            children: [
                logoutButton = $({
                    tagName: 'button',
                    id: 'logout-button',
                    children: [
                        $('Logout')
                    ]
                }),
                $({
                    tagName: 'h2',
                    children: [
                        $('Friend requests')
                    ]
                }),
                friendRequestList = $({
                    tagName: 'ul',
                    id: 'friend-request-list',
                    children: [
                        $({
                            tagName: 'img',
                            src: 'res/spinner.gif'
                        })
                    ]
                }),
                addFriendBox = $({
                    tagName: 'input',
                    type: 'text',
                    id: 'add-friend-box',
                    placeholder: 'username'
                }),
                addFriendButton = $({
                    tagName: 'button',
                    id: 'add-friend-button',
                    children: [
                        $({
                            tagName: 'img',
                            src: 'res/friend-add.png'
                        })
                    ]
                }),
            ]
        });

        logoutButton.onclick = function () {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/api.php?' + SID);

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var data = JSON.parse(xhr.responseText);
                        if (data.error) {
                            alert(data.error);
                        } else {
                            window.location.reload();
                        }
                    } else {
                        alert("Error! Request returned " + xhr.status + "!");
                    }
                }
            };
            
            xhr.send(JSON.stringify({
                action: 'logout'
            }));
        };

        (function refreshFriendRequests() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/api.php?action=get_friend_requests&' + SID);

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var data = JSON.parse(xhr.responseText);
                        if (data.error) {
                            alert(data.error);
                        } else {
                            if (data.requests.length === 0) {
                                friendRequestList.innerHTML = 'You have not received any requests';
                            } else {
                                friendRequestList.innerHTML = '';
                                data.requests.forEach(function (request) {
                                    var friendRequestAccept, friendRequestDeny;
                                    $({
                                        tagName: 'li',
                                        parentElement: friendRequestList,
                                        children: [
                                            $(request.username),
                                            friendRequestAccept = $({
                                                tagName: 'button',
                                                className: 'friend-request-accept'
                                            }),
                                            friendRequestDeny = $({
                                                tagName: 'button',
                                                className: 'friend-request-deny'
                                            })
                                        ]
                                    });
                                    friendRequestAccept.onclick = friendRequestDeny.onclick = function () {
                                        var mode = (this === friendRequestAccept) ? 'accept' : 'deny';

                                        var xhr = new XMLHttpRequest();
                                        xhr.open('POST', '/api.php?' + SID);

                                        xhr.onreadystatechange = function () {
                                            if (xhr.readyState === 4) {
                                                if (xhr.status === 200) {
                                                    var data = JSON.parse(xhr.responseText);
                                                    if (data.error) {
                                                        alert(data.error);
                                                    } else {
                                                        alert((mode === 'accept' ? 'Accepted' : 'Denied') + " friend request.");
                                                        refreshFriendRequests();
                                                    }
                                                } else {
                                                    alert("Error! Request returned " + xhr.status + "!");
                                                }
                                            }
                                        };
                                        
                                        xhr.send(JSON.stringify({
                                            action: 'friend_request_respond',
                                            friend_user_id: request.user_id,
                                            mode: mode
                                        }));
                                    };
                                })
                            }
                        }
                    } else {
                        alert("Error! Request returned " + xhr.status + "!");
                    }
                }
            };
        
            xhr.send();
        }());

        addFriendButton.onclick = function () {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/api.php?' + SID);
    
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var data = JSON.parse(xhr.responseText);
                        if (data.error) {
                            alert(data.error);
                        } else {
                            alert("Sent friend request!");
                            addFriendBox.value = '';
                        }
                    } else {
                        alert("Error! Request returned " + xhr.status + "!");
                    }
                }
            };
            
            xhr.send(JSON.stringify({
                action: 'add_friend',
                username: addFriendBox.value
            }));
        };
        
        return friendRequests;
    }

    // Displays letter browsing screen
    function browse(context, letters, SID) {
        context.topScreen.innerHTML = context.bottomScreen.innerHTML = '';
        $({
            parentElement: context.topScreen,
            tagName: 'div',
            id: 'preview-area'
        });

        var friendRequests = makeFriendRequests(SID);

        var composeButton, friendsButton, letterCarousel, leftButton, rightButton;
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
                    ]
                }),
                friendsButton = $({
                    tagName: 'button',
                    id: 'friends-button',
                    children: [
                        $({
                            tagName: 'img',
                            src: 'res/friends.png'
                        })
                    ]
                }),
                friendRequests 
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
            $({
                tagName: 'div',
                className: 'letter-preview-meta',
                parentElement: letterCarousel,
                style: {
                    left: x + 'px',
                },
                children: [
                    letter.own
                    ? $({
                        tagName: 'button',
                        className: 'letter-preview-send',
                        style: {
                            left: x + 'px'
                        },
                        children: [
                            $("Send")
                        ],
                        onclick: function () {
                            sendLetter(letter.letter_id, context, SID);
                        }
                    })
                    : $("from: " + letter.from_username)
                ]
            });
            if (letter.own) {
                elem.className += ' letter-preview-own';
            } else if (!+letter.read) {
                elem.className += ' letter-preview-read';
            }
            elem.onclick = function () {
                updateCarousel(i);
                loadLetter(context, letter.letter_id, SID);
                elem.className = ("" + elem.className).replace(/letter-preview-read/, '');
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
        
        friendsButton.onclick = function () {
            if (friendRequests.className === 'hidden') {
                friendRequests.className = '';
            } else {
                friendRequests.className = 'hidden';
            }
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

    // Makes request for list of friends then pops up a list to send a letter
    function sendLetter(letterID, context, SID) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/api.php?action=get_possible_recipients&letter_id=' + letterID + '&' + SID);

        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4) {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    if (data.error) {
                        alert(data.error);
                    } else {
                        context.bottomScreen.removeChild(loadingScreen);

                        var friends, friendList, sendButton, cancelButton;
                        friends = $({
                            parentElement: context.bottomScreen,
                            tagName: 'div',
                            id: 'friends',
                            children: [
                                $({
                                    tagName: 'h2',
                                    children: [
                                        $('Send letter')
                                    ]
                                }),
                                friendList = $({
                                    tagName: 'ul',
                                    id: 'friend-list'
                                }),
                                sendButton = $({
                                    tagName: 'button',
                                    id: 'send-button',
                                    children: [
                                        $("Send")
                                    ]
                                }),
                                cancelButton = $({
                                    tagName: 'button',
                                    id: 'cancel-button',
                                    children: [
                                        $("Cancel")
                                    ]
                                })
                            ]
                        });
                        if (data.friends.length === 0) {
                            friendList.innerHTML = 'You have no friends that haven\'t yet been sent this letter';
                        } else {
                            friendList.innerHTML = '';
                            data.friends.forEach(function (friend) {
                                friend.chosen = false;
                                $({
                                    tagName: 'li',
                                    parentElement: friendList,
                                    children: [
                                        $({
                                            tagName: 'input',
                                            type: 'checkbox',
                                            checked: false,
                                            onchange: function () {
                                                friend.chosen = this.checked;
                                            }
                                        }),
                                        $(friend.username)
                                    ]
                                });
                            });
                        }
                        cancelButton.onclick = function () {
                            context.bottomScreen.removeChild(friends);
                        };
                        sendButton.onclick = function () {
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', '/api.php?' + SID);
                    
                            xhr.onreadystatechange = function () {
                                if (xhr.readyState === 4) {
                                    if (xhr.status === 200) {
                                        var data = JSON.parse(xhr.responseText);
                                        if (data.error) {
                                            alert(data.error);
                                        } else {
                                            alert("Sent letter!");
                                            context.bottomScreen.removeChild(friends);
                                        }
                                    } else {
                                        alert("Error! Request returned " + xhr.status + "!");
                                    }
                                }
                            };
                            
                            var friendIDs = [];
                            data.friends.forEach(function (friend) {
                                if (friend.chosen) {
                                    friendIDs.push(friend.id);
                                }
                            });

                            xhr.send(JSON.stringify({
                                action: 'send_letter',
                                letter_id: letterID,
                                friend_ids: friendIDs
                            }));
                        };
                    }
                } else {
                    alert("Error! Request returned " + xhr.status + "!");
                }
            }
        };

        xhr.send();

        var loadingScreen = loading(context.bottomScreen, 'Loading friends list...');
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

        loading(context.bottomScreen, 'Loading letters...');
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
                        $('With PictoSwap, you can draw messages and send them to your friends on other 3DS systems!'),
                    ]
                }),
                $({
                    tagName: 'p',
                    children: [
                        $('Follow '),
                        $({
                            tagName: 'a',
                            href: 'https://twitter.com/PictoSwap',
                            children: [
                                $('@PictoSwap')
                            ]
                        }),
                        $(' on Twitter for updates!')
                    ]
                }),
                $({
                    tagName: 'p',
                    children: [
                        $({
                            tagName: 'a',
                            href: 'https://github.com/TazeTSchnitzel/PictoSwap',
                            children: [
                                $('By Andrea Faulds, 2013-2014. Fork me on GitHub.')
                            ]
                        })
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
