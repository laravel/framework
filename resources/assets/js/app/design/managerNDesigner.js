//include needed packages
require('../../bootstrap');
var jquery = require('jquery');
require('magnific-popup');

//Register Vue components
import UserInformation from '../../components/design/userInformation';
import SiteInformation from '../../components/design/siteInformation';
import CommentSection from '../../components/design/viewcommentsection';
import AttachmentSection from '../../components/design/attachmentsection';
import OverlayNotification from '../../components/overlayNotification';

// Magnific popup call back function.
import PopUpObj from "./PopUpObj";
let PopUpObjAttr = new PopUpObj;
    
var VueInstance = new Vue({
    el: "#DesignViewBlock",
    data: {
        Offset: 10,
        CustomerDetails: UserDetails,
        SiteDetails: CustomerSiteDetails,
        DesignDetails: DesignData,
        CommentsData: Comments,
        Attachments: Object.values(DesignAttachments),
        AttachmentCount: LatestVersion,
        PageUrl: Baseurl,
        DesignStatuses: DesignStatus,
        Loader: true,
        FormOverLay:true,
        NotificationIcon: "",
        NotificationMessage:""
    },
    
    created(){
        this.imageNPdfPopup();
    },
    //Vue components
    components: {
        'user-information': UserInformation,
        'site-information': SiteInformation,
        'comment-section': CommentSection,
        'attachment-section': AttachmentSection,
        'overlay-notification': OverlayNotification
    },
    methods:{
        //initialize image and pdf popup.
        imageNPdfPopup() {
            this.$nextTick(function () {
                jquery('.design-img').each(function () { // the containers for all your galleries
                    jquery(this).magnificPopup({
                        delegate: 'a.image', // the selector for gallery item
                        type: 'image',
                        gallery: {
                            enabled: true
                        },
                        callbacks: PopUpObjAttr.callBack()
                    });
                });
                jquery('.attachment_container').each(function () { // the containers for all your galleries
                    jquery(this).magnificPopup({
                        delegate: 'a.iframe', // the selector for gallery item
                        type: 'iframe',
                        gallery: {
                            enabled: true
                        },
                        callbacks: PopUpObjAttr.callBack()
                    });
                });
                jquery('.attachments-container').each(function () { // the containers for all your galleries
                    jquery(this).magnificPopup({
                        delegate: 'a.image-comment', // the selector for gallery item
                        type: 'image',
                        gallery: {
                            enabled: true
                        },
                        callbacks: PopUpObjAttr.callBack()
                    });
                });
            });
        },
        
        //Show Latest Attachments button action.
        showLatestAttachments(){
            this.Attachments =  _.slice(this.Attachments, 0,1);
            this.imageNPdfPopup();
        },
        
        //Show Latest Comments button action.
        showLatestComments(){
            this.CommentsData.Comments = _.slice(this.CommentsData.Comments, 0, 10);
            this.Offset = 10;
            this.imageNPdfPopup();
        },
        
        getAttachments(Obj)
        {
            this.fetchData(Obj.Url, Obj.Element);
        },
        
        //Show more attachments and comments remote call function.
        fetchData(Url, Element)
        {
            this.Loader = false;
            let SelfRef = this;
            axios.get(Url)
            .then(function (response) {
                if(Element === "Attachments"){
                    SelfRef.assignData(response.data);
                }
                if(Element === "Comments"){
                    SelfRef.assignComment(response.data);
                }
            })
            .catch(function (error) {
               SelfRef.onFail(error);
            })
            .then(() => {
               this.Loader = true;
            });
        },
        
        //Assign attachment data.
        assignData(Data){
            this.Attachments = Object.values(Data.Attachments).reverse();
            this.imageNPdfPopup();
        },
        
         //Assign comment data.
        assignComment(Data){
            this.CommentsData.Comments = _.concat(this.CommentsData.Comments,Data.Comments);
            this.CommentsData.CommentsCount = Data.Count;
            this.Offset = this.Offset + 10;
            this.imageNPdfPopup();
        },
         
        onFail(error) {
            this.populateNotifications({
                status: "error",
                message: AlertData["10077"]
            });
        },
        
        /**
         * Populates notifications of the form.
         *
         * @param  object  response
         * @return void
         */
        populateNotifications(response)
        {
            this.NotificationMessage = response.message;
            this.NotificationIcon = response.status;
            this.FormOverLay = false;
            
            if (response.status === "success") {
                this.NotificationIcon = "check-circle";
                this.NotificationMessage = response.message;

            }else if (response.status === 'error') {
                this.NotificationIcon = "ban";
            }
        },
        
        /**
        * Clears overlay message.
        * @return  No
        */
        clearOverLayMessage()
        {
           this.FormOverLay = true;
           this.NotificationMessage = "";
           this.NotificationIcon = "";
        }
    }
});    

$(document).ready(function(){
    $(document).keyup(function(event) {
        if (event.key === "Escape") {
            VueInstance.clearOverLayMessage();
        }
    });
    
    $(window).on('scroll', function (event) {
        if ($('#ReplyListHeader').length > 0) {
            let ScreenHeight = $(this).scrollTop();
            let divHeight = $('#ReplyListHeader').offset().top;
            if (ScreenHeight >= divHeight) {
                $("#ReplyListStickyHeader").removeClass('hidden').width($("#ReplyListHeader").width());
            } else {
                $("#ReplyListStickyHeader").addClass('hidden');
            }
        }

    });
});