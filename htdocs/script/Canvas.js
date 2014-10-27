(function (PictoSwap) {
    'use strict';
    // Canvas class
    PictoSwap.Canvas = function (width, height) {
        var cv = $({
            tagName: 'canvas',
            className: 'canvas',
            width: width,
            height: height,
            style: {
                position: 'relative',
            }
        });

        var ctx = cv.getContext('2d');

        var strokes = [];
        
        var currentStroke = null, lastX = 0, lastY = 0;

        // Workaround for webkit bugs where element isn't redrawn
        // From: http://stackoverflow.com/a/3485654/736162
        function forceRepaint(elem) {
            var oldDisplay;
            oldDisplay = elem.style.display;
            elem.style.display = 'none';
            elem.offsetHeight;
            elem.style.display = oldDisplay;
        }

        // Draws segment to canvas
        function draw(segment) {
            if (segment.type === 'line') {
                ctx.strokeStyle = segment.colour;
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.moveTo(segment.from_x, segment.from_y);
                ctx.lineTo(segment.x, segment.y);
                ctx.stroke();
            } else if (segment.type === 'dot') {
                ctx.fillStyle = segment.colour;
                ctx.fillRect(segment.x - (3/2), segment.y - (3/2), 3, 3);
            }
            forceRepaint(cv);
        }

        // Clears canvas
        function clear() {
            cv.width = cv.width;
        }

        // Clears all strokes
        function clearStrokes() {
            strokes = [];
            clear();
        }

        // Begins a stroke
        function beginStroke() {
            currentStroke = [];
        }

        // Ends a stroke
        function endStroke() {
            strokes.push(currentStroke);
            currentStroke = null;
        }

        // Exports stroke list so it can be imported
        function exportStrokes() {
            return strokes;
        }

        // Imports stroke list
        function importStrokes(newStrokes, redraw) {
            strokes = newStrokes;

            if (redraw) {
                // Redraw with new stroke set
                replay(true);
            }
        };

        // Adds a line to the current stroke and draws it
        function addLine(colour, fromX, fromY, x, y) {
            var segment = {
                type: 'line',
                from_x: fromX,
                from_y: fromY,
                x: x,
                y: y,
                colour: colour,
                time: (new Date()).getTime()
            };
            draw(segment);
            currentStroke.push(segment);
        }

        // Adds a dot to the current stroke and draws it
        function addDot(colour, x, y) {
            var segment = {
                type: 'dot',
                x: x,
                y: y,
                colour: colour,
                time: (new Date()).getTime()
            };
            draw(segment);
            currentStroke.push(segment);
        }

        // Replays all strokes
        // Has two modes:
        // instant = false: Draws everything immediately.
        // instant = true:  "Plays back" at original speed. Returns a function
        //                  which will stop playback if called. The optional
        //                  callback function is otherwise called upon
        //                  completion.
        function replay(instant, callback) {
            clear();
            if (instant) {
                strokes.forEach(function (stroke) {
                    stroke.forEach(draw);
                });
            } else {
                // Make sure we actually have something to draw
                if (strokes.length && strokes[0].length) {
                    var i = 0, j = 0, stop = false;

                    function nextSegment() {
                        if (stop) {
                            return;
                        }

                        var beginTime = (new Date()).getTime();
                        var segment = strokes[i][j];
                        draw(segment);
                        var endTime = (new Date()).getTime();

                        // Move to next segment of stroke
                        j++;

                        // Reached end of this stroke, move to next
                        if (j >= strokes[i].length) {
                            j = 0;
                            i++;
                        }

                        // Reached end of strokes, we're done here
                        if (i >= strokes.length) {
                            if (callback) {
                                 callback();
                            }
                            return;
                        }

                        var newSegment = strokes[i][j];

                        // We ignore the actual time between strokes
                        // Due to 2s hold delay and other reasons
                        if (j === 0) {
                            setTimeout(nextSegment, 50);
                        } else {
                            //setTimeout(nextSegment, ((newSegment.time - segment.time) / 4) - (beginTime - endTime));
                            setTimeout(nextSegment, 1);
                        }
                    }

                    nextSegment();

                    // Return function which stops playback
                    return function () {
                        stop = true;
                    };
                } else {
                    if (callback) {
                        callback();
                    }
                    return function () {};
                }
            }
        }

        return {
            clearStrokes: clearStrokes,
            beginStroke: beginStroke,
            endStroke: endStroke,
            addLine: addLine,
            addDot: addDot,
            importStrokes: importStrokes,
            exportStrokes: exportStrokes,
            replay: replay,
            element: cv
        };
    };
}(window.PictoSwap = window.PictoSwap || {}));
