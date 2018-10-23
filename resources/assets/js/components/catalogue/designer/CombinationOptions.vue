<template>
<div class="row">
    <div class="col-md-12 col-xs-12 pd-tp-5 pd-bt-7">
        <div class="form-group">
            <label>
                Combination
                <i class="fa fa-question-circle cursor-pointer help-icon" data-toggle="tooltip" data-original-title="Please select laminates from any option below to create combination">
                </i>
            </label>
            <p>
                <input type="radio" name="SuggestionType" value="HechpeSuggs" v-model="Selection" class="input-radio" id="HechpeSuggs"/>
                <label for="HechpeSuggs" tabindex="0"></label>
                <label for="HechpeSuggs" class="text-normal cursor-pointer mr-rt-20">HECHPE Suggestions</label>
                <input type="radio" name="SuggestionType" value="PickFromShortlist" v-model="Selection" class="input-radio" id="PickFromShortlist" />
                <label for="PickFromShortlist" tabindex="-1"></label>
                <label for="PickFromShortlist" class="text-normal cursor-pointer mr-rt-20">Pick from shortlist</label>
                <input type="radio" name="SuggestionType" value="GenSuggs" v-model="Selection" class="input-radio" id="GenSuggs"/> 
                <label for="GenSuggs" tabindex="-1"></label>
                <label for="GenSuggs" class="text-normal cursor-pointer">Search</label> 
            </p>
        </div> 
    </div>
</div>
</template>

<script>
    // Child component
    export default {
        data() {
            return {
                Selection: ''
            };
        },
        created() {      
            this.Selection = this.$root.PickedOption;
        },
        watch: {
            Selection: function(option) {
               this.$root.PickedOption = option;
            }
        }           
    }
</script>