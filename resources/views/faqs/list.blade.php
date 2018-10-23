@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ asset('/css/materials/vueTable.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{asset('AdminLTE/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css')}}">
<link rel="stylesheet" href="{{ asset('css/faqs/editor.css') }}">
<link rel="stylesheet" href="{{ asset('css/faqs/faqs.css') }}">   
@endsection

@section('content')
<div id="ListFaqs" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" data-toggle="tooltip" title="Click here to Add new Category" @click.prevent="addModal"> <i class="fa fa-fw fa-plus-square"></i> New FAQ</a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="callout callout-info mr-tp-6 mr-bt-6" v-if="_.size(faqs) === 0">
                        <p>No FAQs found.</p>
                    </div>
                    <v-client-table :columns="columns" :data="filteredFaqs" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <span slot="Answer" slot-scope="props" v-html="sliceAnswer(props.row.Answer)"></span>
                        <span slot="Question" slot-scope="props">@{{sliceQuestion(props.row.Question)}}</span>
                        <template slot="IsActive" slot-scope="props">
                            <span  class="label label-success" v-if="props.row.IsActive==1">Active</span>
                            <span class="label label-danger" v-else>InActive</span>
                        </template>
                        <template slot="Actions" slot-scope="props">
                            <a class="btn btn-custom btn-edit btn-sm mr-rt-3" data-toggle="tooltip" data-original-title="Edit" role="button" @click.prevent="updateModal(props.row)">
                                <span class="glyphicon glyphicon-pencil btn-edit"></span>
                            </a>
                            <a class="btn btn-custom btn-edit btn-sm" data-toggle="tooltip" data-original-title="View" role="button" @click.prevent="viewModal(props.index, props.row)">
                                <span class="glyphicon glyphicon-eye-open btn-edit"></span>
                            </a>
                        </template>
                    </v-client-table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" role="dialog" id="AddModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add FAQ</h4>
                </div>
                <div class="modal-body pd-bt-0">
                    <form :action="Url+'faq/add'" method="POST" accept-charset="utf-8" id="addForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="FaqCategory">FAQ Category*</label>
                                    <select name="FaqCategory" id="FaqCategory" class="form-control placeholder-placement">
                                        <option value="">Select a FaqCategory</option>
                                        <option v-for="category in categories" :value="category.Id" >@{{ category.Name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="Image">Image</label>
                                    <input type="file" name="Image" id="Image" class="form-control"accept="image/*"/>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Question">Question*</label>
                            <input type="text" name="Question" id="Question" class="form-control" placeholder="Ex: Question"/>
                        </div>
                        <div class="form-group">
                            <label for="Answer">Answer*</label>
                            <div class="editor">
                                <div id="toolbar" class="edy-tb" style="">
                                    <button class="edy-tb-cmd" data-wysihtml5-command="bold" title="Bold" unselectable="on">
                                        <span class="fa fa-bold"></span>
                                    </button>
                                    <button class="edy-tb-cmd" data-wysihtml5-command="italic" title="Italic" unselectable="on">
                                        <span class="fa fa-italic"></span>
                                    </button>
                                    <button class="edy-tb-cmd" data-wysihtml5-command="underline" title="Underline" unselectable="on">
                                        <span class="fa fa-underline"></span>
                                    </button>
                                    <div class="edy-tb-menucontainer dropdown">
                                        <button data-toggle="dropdown" class="dropdown-toggle edy-tb-act edy-tb-paragraph edy-tb-g" data-behavior="showstyles" title="Styles">
                                            <span class="fa fa-header"></span> <span class="fa fa-caret-down"></span>
                                        </button>
                                        <div class="edy-tb-stylemenu dropdown-menu">
                                            <button class="edy-tb-cmd edy-tb-style-h1" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="h1" unselectable="on">Title</button>
                                            <button class="edy-tb-cmd edy-tb-style-h2" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="h2" unselectable="on">Heading</button>
                                            <button class="edy-tb-cmd edy-tb-style-h3" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="h3" unselectable="on">Subheading</button>
                                            <button class="edy-tb-cmd edy-tb-style-p" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="p" unselectable="on">Normal text</button>
                                            <button class="edy-tb-cmd edy-tb-style-pre" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="pre" unselectable="on">Fixed width</button>
                                            <button class="edy-tb-cmd edy-tb-style-blockquote" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="blockquote" unselectable="on">Quote block</button>
                                        </div>
                                    </div>
                                    <div class="edy-tb-menucontainer createlink">
                                        <button class="edy-tb-cmd edy-tb-g" data-wysihtml5-command="createLink" title="Create link" unselectable="on">
                                            <span class="fa  fa-link"></span>
                                        </button>
                                        <div class="edy-reset edy-popover edy-itempicker" data-wysihtml5-dialog="createLink" style="display: none;">
                                            <div class="edy-popover-content">
                                                <div class="edy-form-group edy-tibtn-container">
                                                    <input type="text" name="query" id="createlinktextfield" class="edy-form-control edy-input-large" placeholder="Type link URL here..." autocomplete="off" data-wysihtml5-dialog-field="href" value="http://">
                                                    <div class="edy-itempicker-input-btns">
                                                        <button class="edy-btn edy-btn-large edy-btn-green" id="insertlink" data-wysihtml5-dialog-action="" type="button">Insert</button>
                                                        <button class="edy-btn edy-btn-large edy-btn-green" data-wysihtml5-command="removeLink" unselectable="on">Remove</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="edy-tb-cmd edy-tb-g" data-wysihtml5-command="Outdent" title="Outdent" unselectable="on">
                                        <span class="fa fa-outdent"></span>
                                    </button>
                                    <button class="edy-tb-cmd edy-tb-g" data-wysihtml5-command="Indent" title="Indent" unselectable="on">
                                        <span class="fa  fa-indent"></span>
                                    </button>
                                    <button class="edy-tb-cmd edy-tb-g" data-wysihtml5-command="insertUnorderedList" title="Insert bulleted list" unselectable="on">
                                        <span class="fa  fa-list-ul"></span>
                                    </button>
                                    <button class="edy-tb-cmd" data-wysihtml5-command="insertOrderedList" title="Insert numbered list" unselectable="on">
                                        <span class="fa  fa-list-ol"></span>
                                    </button>
                                    <div class="edy-tb-menucontainer dropdown">
                                        <button data-toggle="dropdown" class="dropdown-toggle edy-tb-act edy-tb-color edy-tb-g" data-behavior="foreColor" title="Text color">
                                           <span class="glyphicon glyphicon-text-color"></span>
                                        </button>
                                        <div class="dropdown-menu edy-tb-dropdown edy-tb-color-modal">
                                          <div class="edy-tb-color-tab-content">
                                            <div class="edy-colorpicker">
                                              <div class="edy-colorpicker-colors">
                                                <p class="mr-bt-15">Text color</p>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="white" style="background-color: white;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="lightgray" style="background-color: lightgray;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="gray" style="background-color: gray;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="black" style="background-color: black;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="aqua" style="background-color: aqua;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="cornflowerblue" style="background-color: cornflowerblue;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="blue" style="background-color: blue;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="darkblue" style="background-color: darkblue;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="cadetblue" style="background-color: cadetblue;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="lawngreen" style="background-color: lawngreen;" class="colorpicker-color-bordered" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="green"  style="background-color: green;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="olive" style="background-color: olive;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="yellow" style="background-color: yellow;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="orange" style="background-color: orange;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="red" style="background-color: red;" class="colorpicker-color-bordered" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="maroon" style="background-color: maroon;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="fuchsia" style="background-color: fuchsia;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="purple" style="background-color: purple;" unselectable="on"></div>
                                              </div>
                                            </div>
                                            <div class="edy-colorpicker">
                                              <div class="edy-colorpicker-colors">
                                                <p class="mr-tp-68 mr-bt-15">Background color</p>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(255,255,255)" style="background-color: rgb(255,255,255);" class="colorpicker-color-bordered" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(192,192,192)" style="background-color: rgb(192,192,192);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(128,128,128)" style="background-color: rgb(128,128,128);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,0,0)" style="background-color: rgb(0,0,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,255,255)" style="background-color: rgb(0,255,255);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0, 162, 255)" style="background-color: rgb(0, 162, 255);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,0,255)" style="background-color: rgb(0,0,255);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,0,128)" style="background-color: rgb(0,0,128);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,128,128)" style="background-color: rgb(0,128,128);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,255,0)" style="background-color: rgb(0,255,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,128,0)" style="background-color: rgb(0,128,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(128,128,0)" style="background-color: rgb(128,128,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(255,255,0)" style="background-color: rgb(255,255,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(255,165,0)"  style="background-color: rgb(255,165,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(255,0,0)" style="background-color: rgb(255,0,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(128,0,0)" style="background-color: rgb(128,0,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(255,0,255)" style="background-color: rgb(255,0,255);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(128,0,128)" style="background-color: rgb(128,0,128);" unselectable="on"></div>
                                              </div>
                                            </div>
                                          </div>
                                        </div>
                                      </div>
                                </div>
                                <textarea type="text" name="Answer" id="Answer" rows="5" class="textarea form-control" placeholder=""></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="Status" id="Active" value="1" class="input-radio"/>
                                <label for="Active" tabindex="0"></label>
                                <label for="Active" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="Status" id="Inactive" value="0" class="input-radio"/>
                                <label for="Inactive" tabindex="0"></label>
                                <label for="Inactive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary pull-left button-custom" >Save</button>
                            <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
                <div class="form-overlay" :class="{hidden: SaveLoader}">
                     <div class="large loader"></div>
                    <div class="loader-text">Saving Category</div>
                </div>
                <div id="NotificationArea"></div>
            </div>
        </div>
    </div>

    <!-- Update Modal -->
    <div class="modal fade" role="dialog" id="UpdateModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Update FAQ</h4>
                </div>
                <div class="modal-body pd-bt-0">
                    <form :action="Url+'faq/update/'+selectedFaqData.Id" method="POST" accept-charset="utf-8" id="UpdateForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="EditFaqCategory">FAQ Category*</label>
                                    <select name="FaqCategory" id="EditFaqCategory" class="form-control placeholder-placement">
                                        <option value="">Select a FaqCategory</option>
                                        <option v-for="category in categories" :value="category.Id" :selected="category.Id===selectedFaqData.FAQCategoryId">@{{ category.Name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="EditImage">Image</label>
                                    <input type="file" name="Image" id="EditImage" class="form-control"accept="image/*"/>
                                    <small v-html="fileName(selectedFaqData.Image)"></small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="EditQuestion">Question*</label>
                            <input type="text" name="Question" id="EditQuestion" :value="selectedFaqData.Question" class="form-control" placeholder="Ex: Question"/>
                        </div>
                        <div class="form-group">
                            <label for="EditAnswer">Answer*</label>
                            <div class="editor">
                                <div id="edittoolbar" class="edy-tb" style="">
                                    <button type="button" class="edy-tb-cmd" data-wysihtml5-command="bold" title="Bold" unselectable="on">
                                        <span class="fa fa-bold"></span>
                                    </button>
                                    <button class="edy-tb-cmd" data-wysihtml5-command="italic" title="Italic" unselectable="on">
                                        <span class="fa fa-italic"></span>
                                    </button>
                                    <button class="edy-tb-cmd" data-wysihtml5-command="underline" title="Underline" unselectable="on">
                                        <span class="fa fa-underline"></span>
                                    </button>
                                    <div class="edy-tb-menucontainer dropdown">
                                        <button data-toggle="dropdown" class="dropdown-toggle edy-tb-act edy-tb-paragraph edy-tb-g" data-behavior="showstyles" title="Styles">
                                            <span class="fa fa-header"></span> <span class="fa fa-caret-down"></span>
                                        </button>
                                        <div class="edy-tb-stylemenu dropdown-menu">
                                            <button class="edy-tb-cmd edy-tb-style-h1" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="h1" unselectable="on">Title</button>
                                            <button class="edy-tb-cmd edy-tb-style-h2" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="h2" unselectable="on">Heading</button>
                                            <button class="edy-tb-cmd edy-tb-style-h3" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="h3" unselectable="on">Subheading</button>
                                            <button class="edy-tb-cmd edy-tb-style-p" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="p" unselectable="on">Normal text</button>
                                            <button class="edy-tb-cmd edy-tb-style-pre" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="pre" unselectable="on">Fixed width</button>
                                            <button class="edy-tb-cmd edy-tb-style-blockquote" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="blockquote" unselectable="on">Quote block</button>
                                        </div>
                                    </div>
                                    <div class="edy-tb-menucontainer createlink">
                                        <button class="edy-tb-cmd edy-tb-g" data-wysihtml5-command="createLink" title="Create link" unselectable="on">
                                            <span class="fa  fa-link"></span>
                                        </button>
                                        <div class="edy-reset edy-popover edy-itempicker" data-wysihtml5-dialog="createLink" style="display: none;">
                                            <div class="edy-popover-content">
                                                <div class="edy-form-group edy-tibtn-container">
                                                    <input type="text" name="query" class="edy-form-control edy-input-large" id="updatelinktextfield" placeholder="Type link URL here..." autocomplete="off" data-wysihtml5-dialog-field="href" value="http://">
                                                    <div class="edy-itempicker-input-btns">
                                                        <button class="edy-btn edy-btn-large edy-btn-green" id="updateinsertlink" type="button" data-wysihtml5-dialog-action="">Insert</button>
                                                        <button class="edy-btn edy-btn-large edy-btn-green" data-wysihtml5-command="removeLink" unselectable="on">Remove</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="edy-tb-cmd edy-tb-g" data-wysihtml5-command="Outdent" title="Outdent" unselectable="on">
                                        <span class="fa fa-outdent"></span>
                                    </button>
                                    <button class="edy-tb-cmd edy-tb-g" data-wysihtml5-command="Indent" title="Indent" unselectable="on">
                                        <span class="fa  fa-indent"></span>
                                    </button>
                                    <button class="edy-tb-cmd edy-tb-g" data-wysihtml5-command="insertUnorderedList" title="Insert bulleted list" unselectable="on">
                                        <span class="fa  fa-list-ul"></span>
                                    </button>
                                    <button class="edy-tb-cmd" data-wysihtml5-command="insertOrderedList" title="Insert numbered list" unselectable="on">
                                        <span class="fa  fa-list-ol"></span>
                                    </button>
                                    <div class="edy-tb-menucontainer dropdown">
                                        <button data-toggle="dropdown" class="dropdown-toggle edy-tb-act edy-tb-color edy-tb-g" data-behavior="foreColor" title="Text color">
                                           <span class="glyphicon glyphicon-text-color"></span>
                                        </button>
                                        <div class="dropdown-menu edy-tb-dropdown edy-tb-color-modal">
                                          <div class="edy-tb-color-tab-content">
                                            <div class="edy-colorpicker">
                                              <div class="edy-colorpicker-colors">
                                                <p class="mr-bt-15">Text color</p>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="white" style="background-color: white;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="lightgray" style="background-color: lightgray;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="gray" style="background-color: gray;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="black" style="background-color: black;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="aqua" style="background-color: aqua;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="cornflowerblue" style="background-color: cornflowerblue;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="blue" style="background-color: blue;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="darkblue" style="background-color: darkblue;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="cadetblue" style="background-color: cadetblue;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="lawngreen" style="background-color: lawngreen;" class="colorpicker-color-bordered" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="green"  style="background-color: green;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="olive" style="background-color: olive;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="yellow" style="background-color: yellow;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="orange" style="background-color: orange;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="red" style="background-color: red;" class="colorpicker-color-bordered" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="maroon" style="background-color: maroon;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="fuchsia" style="background-color: fuchsia;" unselectable="on"></div>
                                                <div data-wysihtml5-command="foreColor" data-wysihtml5-command-value="purple" style="background-color: purple;" unselectable="on"></div>
                                              </div>
                                            </div>
                                              <div class="edy-colorpicker">
                                              <div class="edy-colorpicker-colors">
                                               
                                                <p class="mr-tp-68 mr-bt-15">Background color</p>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(255,255,255)" style="background-color: rgb(255,255,255);" class="colorpicker-color-bordered" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(192,192,192)" style="background-color: rgb(192,192,192);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(128,128,128)" style="background-color: rgb(128,128,128);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,0,0)" style="background-color: rgb(0,0,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,255,255)" style="background-color: rgb(0,255,255);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0, 162, 255)" style="background-color: rgb(0, 162, 255);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,0,255)" style="background-color: rgb(0,0,255);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,0,128)" style="background-color: rgb(0,0,128);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,128,128)" style="background-color: rgb(0,128,128);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,255,0)" style="background-color: rgb(0,255,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(0,128,0)" style="background-color: rgb(0,128,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(128,128,0)" style="background-color: rgb(128,128,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(255,255,0)" style="background-color: rgb(255,255,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(255,165,0)"  style="background-color: rgb(255,165,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(255,0,0)" style="background-color: rgb(255,0,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(128,0,0)" style="background-color: rgb(128,0,0);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(255,0,255)" style="background-color: rgb(255,0,255);" unselectable="on"></div>
                                                <div data-wysihtml5-command="bgColorStyle" data-wysihtml5-command-value="rgb(128,0,128)" style="background-color: rgb(128,0,128);" unselectable="on"></div>
                                              </div>
                                            </div>
                                          </div>
                                        </div>
                                      </div>
                                </div>
                                    <textarea type="text" name="Answer" id="EditAnswer" rows="5" class="form-control" >@{{selectedFaqData.Answer}}</textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="Status" id="UpdateActive" value="1" class="input-radio" :checked="selectedFaqData.IsActive==1"/>
                                <label for="UpdateActive" tabindex="0"></label>
                                <label for="UpdateActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="Status" id="UpdateInactive" value="0" class="input-radio" :checked="selectedFaqData.IsActive==0"/>
                                <label for="UpdateInactive" tabindex="0"></label>
                                <label for="UpdateInactive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary pull-left button-custom" >Update</button>
                            <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
                <div class="form-overlay" :class="{hidden: UpdateLoader}">
                     <div class="large loader"></div>
                    <div class="loader-text">Updating Category</div>
                </div>
                <div id="UpdateNotificationArea"></div>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="ViewModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">View</h4>
                </div>
                <div class="modal-body">
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr>
                                <td>#</td>
                                <td>@{{SelectedFaqIndex}}</td>
                            </tr>
                            <tr>
                                <td>FAQ Category</td>
                                <td>@{{selectedFaqData.FaqCategory}}</td>
                            </tr>
                            <tr>
                                <td>Question</td>
                                <td>@{{selectedFaqData.Question}}</td>
                            </tr>
                            <tr>
                                <td>Answer</td>
                                <td v-html="selectedFaqData.Answer"></td>
                            </tr>
                            <tr>
                                <td>Image</td>
                                <td>
                                    <img v-if="selectedFaqData.Image" style="width: 50px; height: 50px;" :src="UrlCdn+selectedFaqData.Image">
                                    <span v-else>N/A</span>   
                                </td>
                            </tr>
                            <tr>
                                <td>Status</td>
                                <td>
                                    <span class="label label-success" v-if="selectedFaqData.IsActive==1">Active</span>
                                    <span class="label label-danger" v-else>InActive</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('js/faqs/wysihtml-toolbar.min.js') }}"></script>
<script src="{{ asset('js/faqs/advanced_and_extended.js') }}"></script>
<script src="{{ asset('js/common.js') }}"></script>
<script src="{{ asset('js/faqs/list.js') }}"></script>
@endsection
