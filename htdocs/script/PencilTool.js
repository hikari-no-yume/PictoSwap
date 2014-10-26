(function (PictoSwap) {
	PictoSwap.PencilTool = function (canvas, previewCanvas,inkMeter) {
	var pencilElement;
	var canvas = canvas, previewCanvas = previewCanvas, inkMeter=inkMeter;
	var drawing = false, drawColour = 'black';

	var x,y,lastX,lastY;

		function onCanvasMousedown(e, currentPage){

                    if (pages[currentPage].empty) {
                        saveButton.innerHTML = 'Save'; //TODO: Find a way to have this be called by the current tool
                    }

                    // Begin a stroke
                    drawing = true;
                    canvas.beginStroke();
                    previewCanvas.beginStroke();

                    // Draw dot
                    // We cache x and y to avoid bizzare browser bugs
                   // Don't ask me why, but the second time you read e.layerX, it becomes zero!
                    var x = e.layerX, y = e.layerY;
                    canvas.addDot(drawColour, x, y);
                    previewCanvas.addDot(drawColour, x, y);

                    // Store the position at present so that we know where next
                    // segment will start
                    lastX = e.layerX;
                    lastY = e.layerY;
		}
		function onCanvasMousemove(e, currentPage){
                    // We cache x and y to avoid bizzare browser bugs
                    // Don't ask me why, but the second time you read e.layerX, it becomes zero!
                   var x = e.layerX, y = e.layerY;
            
                    // Prevent mouse moving causing drawing on desktop
                    // (Obviously, "mousemove" can't fire when not dragging on 3DS)
                    if (drawing && (lastX !== x || lastY !== y)) {
                        // The length of this segment will be subtracted from our "ink"
                        var inkUsed = calcDistance(lastX, lastY, x, y);

                        // Only allow drawing if we have enough ink
                        if (!inkMeter.subtractInk(inkUsed)) {
                            return;
                        }

                        // Amount of ink this page uses
                        currentPage.inkUsage += inkUsed;

                        // Draw line
                        canvas.addLine(drawColour, lastX, lastY, x, y);
                        previewCanvas.addLine(drawColour, lastX, lastY, x, y);
                    }

                    lastX = x;
                    lastY = y;
		}
                function onCanvasMouseup(e, currentPage){
                    if (drawing) {
                        onCanvasMousemove(e, currentPage);

                        // End of stroke
                        canvas.endStroke();
                        previewCanvas.endStroke();
                
                        drawing = false;
                    }
                }
	
		return {
                    onCanvasMousedown:onCanvasMousedown,
                    onCanvasMousemove:onCanvasMousemove,
                    onCanvasMouseup:onCanvasMouseup,
		};
	};
}(window.PictoSwap = window.PictoSwap || {}));
