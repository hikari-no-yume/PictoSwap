(function (PictoSwap) {
	//A class to hold the ink usage and data per page instead of in global arrays.
        PictoSwap.Page = function () {
                var inkUsage=0, empty=true, data=[];
		return {
                inkUsage: inkUsage,
                empty: empty,
                data: data,
		};
	};
}(window.PictoSwap = window.PictoSwap || {}));
