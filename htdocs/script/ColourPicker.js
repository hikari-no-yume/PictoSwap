(function (PictoSwap) {
    // Calculates a distance
    function calcDistance(x1, y1, x2, y2) {
        return Math.sqrt(((x2 - x1) * (x2 - x1)) + ((y2 - y1) * (y2 - y1)));
    }

    // ColourPicker class
    PictoSwap.ColourPicker = function () {
        var element, colourWheel;
        element = $({
            tagName: 'div',
            className: 'colour-picker hidden',
            children: [
                colourWheel = $({
                    // Slow on 3DS, pre-generated BG used instead
                    //tagName: 'canvas',
                    tagName: 'div',
                    className: 'colour-wheel',
                    width: 100,
                    height: 100
                })
            ]
        });

        var callback;

        // Opens colour picker. Chosen colour will be passed to callback cb
        function open(cb) {
            element.className = 'colour-picker';
            callback = cb;
        }

        var radius, boxSize, centreX, centreY;
        radius = (colourWheel.width / 2);
        boxSize = 12;
        centreX = colourWheel.width / 2;
        centreY = colourWheel.height / 2;

        // Returms colour for point on colour wheel
        // If that point is out of bounds, returns false
        function positionToColour(x, y) {
            var distance, angle;

            // Find distance from circle centre
            distance = calcDistance(centreX, centreY, x, y);

            // Upper-left white
            if (x < boxSize && y < boxSize) {
                return 'white';
            // Bottom-right white
            } else if (x > colourWheel.width - boxSize && y > colourWheel.width - boxSize) {
                return 'black';
            // Otherwise stay within radius
            } else if (distance >= radius) {
                return false;
            }

            // Find angle (in degrees) from centre
            angle = (Math.atan2(x - centreX, y - centreY) / (Math.PI * 2)) * 360;

            while (angle < 0) {
                angle += 360;
            }
            while (angle > 360) {
                angle -= 360;
            }

            return 'hsl(' + angle + ', 100%, ' + (100 * (distance / radius)) + '%)';
        }

        // Draws a colour wheel
        function drawColourWheel() {
            var ctx, x, y, colour;

            ctx = colourWheel.getContext('2d');
            for (x = 0; x < colourWheel.width; x++) {
                for (y = 0; y < colourWheel.height; y++) {
                    colour = positionToColour(x, y);
                    if (colour) {
                        ctx.fillStyle = colour;
                        ctx.fillRect(x, y, 1, 1);
                    }
                }
            }
        }

        colourWheel.onclick = function (e) {
            var colour;

            colour = positionToColour(e.layerX, e.layerY);
            if (!colour) {
                return;
            }

            element.className = 'colour-picker hidden';
            callback(colour);
        }

        // This is very slow on the 3DS, hence we use a pre-generated one
        //drawColourWheel();

        return {
            open: open,
            element: element
        };
    };
}(window.PictoSwap = window.PictoSwap || {}));
