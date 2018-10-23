@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('css/faqs/editor.css') }}">
<link rel="stylesheet" href="{{ asset('css/faqs/faqs.css') }}">
<link rel="stylesheet" href="{{ asset('css/faqs/tabs.css') }}">
@endsection

@section('content')
<div id="FaqsPage" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body ">
                    <div class="callout callout-info mr-tp-6 mr-bt-6" v-if="_.size(faqs) === 0">
                        <p>No FAQs found.</p>
                    </div>
                    <div v-else>
                        <div class="callout callout-info" :class="{hidden : _.size(faqs) > 0}">No Results found.</div>
                        <div class="nav-tabs-custom mr-tp-15">
                            <ul class="nav nav-tabs">
                                <li class="active">
                                    <a href="#tab_all" data-toggle="tab" aria-expanded="true">All</a>
                                </li>
                                <li v-for="(faq, key, index) in faqs" >
                                    <a :href="'#tab_'+index" data-toggle="tab" aria-expanded="false">@{{ key }}</a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div id="tab_all" class="tab-pane active">
                                    <div class="box-group" id="accordion_all">
                                        <div class="faq-accordion" v-for="(faq, key, index) in faqs">
                                            <div class="panel box box-primary" v-for="(data, i) in faq">
                                                <div class="box-header">
                                                    <a data-toggle="collapse" data-parent="#accordion_all" :href="'#collapse_alltab_sec_'+index+'_acc_'+i" aria-expanded="false" class="collapsed">
                                                        <h4 class="box-title">
                                                            @{{ data.Question }}
                                                            <i class="fa fa-angle-down pull-right"></i>
                                                        </h4>
                                                    </a>
                                                </div>
                                                <div :id="'collapse_alltab_sec_'+index+'_acc_'+i" class="panel-collapse collapse" aria-expanded="false" style="height: 0px;">
                                                    <div class="box-body faq-boxbody">
                                                        <p v-html="returnAnswer(data)"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div  v-for="(faq, key, index) in faqs" class="tab-pane" :id="'tab_'+index">
                                    <div class="box-group faq-accordion" id="accordion">
                                        <div class="panel box box-primary" v-for="(data, i) in faq">
                                            <div class="box-header">
                                                <a data-toggle="collapse" data-parent="#accordion" :href="'#collapse_tab_'+index+'_acc_'+i" aria-expanded="false" class="collapsed">
                                                    <h4 class="box-title">
                                                        @{{ data.Question }}
                                                        <i class="fa fa-angle-down pull-right"></i>
                                                    </h4>
                                                </a>
                                            </div>
                                            <div :id="'collapse_tab_'+index+'_acc_'+i" class="panel-collapse collapse" aria-expanded="false" style="height: 0px;">
                                                <div class="box-body faq-boxbody">
                                                     <p v-html="returnAnswer(data)"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('js/faqs/faqs.js') }}"></script>
@endsection
