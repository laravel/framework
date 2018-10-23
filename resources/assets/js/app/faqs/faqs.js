
/** include needed packages **/
require('../../bootstrap');

/** initialize Vue instance **/
const VueInstance = new Vue({
    el: '#FaqsPage',
    data: {
        faqs: {},
        UrlCdn: CdnUrl
    },
    mounted() {
       /** Bind the variables which are defined in controller **/
       this.faqs = Faqs;
    },
    methods:{
        returnAnswer(data){
            let imgtag = '';
            if(data.Image){
                imgtag = '<img class="faq-accord-img" src="'+this.UrlCdn+data.Image+'">';
            }
            return imgtag+data.Answer;
        }
    }
});