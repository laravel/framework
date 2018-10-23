var jquery = require('jquery');
/**
 * Magnific PopUp call back function.
 */
class PopUpObj
{
    callBack()
    {
        return {
            open: function () {
                var mfp = jquery.magnificPopup.instance;
                var proto = jquery.magnificPopup.proto;
                var Count = mfp.items.length;
                if (!mfp.index && Count > 1) {
                    mfp.arrowLeft.css('display', 'none');
                }
                if (!(mfp.index - (Count - 1)) && Count > 1) {
                    mfp.arrowRight.css('display', 'none');
                }
                // extend function that moves to next item
                mfp.next = function () {
                    if (mfp.index < (Count - 1)) {
                        proto.next.call(mfp);
                    }
                    if (Count > 1) {
                        if (!(mfp.index - (Count - 1))) {
                            mfp.arrowRight.css('display', 'none');
                        }
                        if (mfp.index > 0) {
                            mfp.arrowLeft.css('display', 'block');
                        }
                    }
                };
                // extend function that moves back to prev item
                mfp.prev = function () {
                    if (mfp.index > 0) {
                        proto.prev.call(mfp);
                    }
                    if (Count > 1) {
                        if (!mfp.index) {
                            mfp.arrowLeft.css('display', 'none');
                        }
                        if (Count > 1) {
                            mfp.arrowRight.css('display', 'block');
                        }
                    }
                };
            }
        };
    }
}

export default PopUpObj;