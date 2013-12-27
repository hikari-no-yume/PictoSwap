(function (swapnote) {
    // InkMeter class
    swapnote.InkMeter = function (ink, maxInk) {
        var meterElement, barElement;
        meterElement = $({
            tagName: 'div',
            className: 'ink-meter',
            children: [
                barElement = $({
                    tagName: 'div',
                    className: 'ink-meter-bar'
                })
            ]
        });

        function updateMeter() {
            barElement.style.height = (100 * (ink / maxInk)) + '%';
        }

        // Resets ink
        function resetInk() {
            ink = maxInk;
            updateMeter();
        }

        // Attempts to subtract from ink. Returns false if there's not enough.
        function subtractInk(amount) {
            if (ink - amount < 0) {
                return false;
            }
            ink -= amount;
            updateMeter();
            return true;
        }

        // Adds to ink.
        function addInk(amount) {
            if (ink + amount > maxInk) {
                ink = maxInk;
            } else {
                ink += amount;
            }
            updateMeter();
        }

        return {
            element: meterElement,
            resetInk: resetInk,
            addInk: addInk,
            subtractInk: subtractInk
        };
    };
}(window.swapnote = window.swapnote || {}));
