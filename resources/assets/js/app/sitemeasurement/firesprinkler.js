
// Include needed packages
require('../../bootstrap');
require('magnific-popup');

// Resolve Jquery library conflict
var $ = require('jquery');

// Initialize Vue instance 
new Vue({
    // Vue root element
    el: "#ViewFireSprinklersPage",

    // Vue data variables
    data: {
        // Bind the variables which are defined in Controller
        CdnUrl: S3CdnUrl,
        Sprinklers: []
    },

    // Vue object life cycle hook 
    mounted() {
        this.Sprinklers = Sprinklers;
    },

    methods: {
       // Initialize popup when user clicks on thumbnail
        initializeThumbnailsPopup(imagesJSON,cdnUrl) {
            // Parse JSON and get image path and title
            let thumbnails = JSON.parse(imagesJSON);
            // Create image object which fits for plugin input
            thumbnails.forEach(function (obj) {
                obj.src = cdnUrl + obj.Path;
                obj.title = obj.UserFileName;
            }.bind(this));
            $(".image-link").magnificPopup({
                items: thumbnails,
                gallery: {
                    enabled: true
                },
                type: 'image',
                callbacks: {  
                    open: function() {                        
                        var mfp = $.magnificPopup.instance;
                        var proto = $.magnificPopup.proto;
                        var Count = mfp.items.length;
                        if(!mfp.index && Count > 1){
                            mfp.arrowLeft.css('display', 'none');
                        }
                        if(!(mfp.index - (Count-1)) && Count > 1){
                            mfp.arrowRight.css('display', 'none');
                        }
                        // Extend function that moves to next item
                        mfp.next = function() {
                            if(mfp.index < (Count-1)) {
                                proto.next.call(mfp);
                            }
                            if(Count > 1){
                                if(!(mfp.index - (Count-1))){
                                    mfp.arrowRight.css('display', 'none');
                                }
                                if(mfp.index > 0){
                                    mfp.arrowLeft.css('display', 'block');
                                }
                            }
                        };
                        // Extend function that moves back to prev item
                        mfp.prev = function() {
                            if(mfp.index > 0) {
                                proto.prev.call(mfp);
                            }
                            if(Count > 1){
                                if(!mfp.index){
                                    mfp.arrowLeft.css('display', 'none');
                                }
                                if(Count > 1){
                                   mfp.arrowRight.css('display', 'block');
                                }
                            }
                        };
                    },
                    close: function () {
                        $('.ui-tooltip').addClass("hidden");
                    }
                }
            });
        }
    }
});