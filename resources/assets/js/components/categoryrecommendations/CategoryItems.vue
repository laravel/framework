<template>
    <div>
        <div class="callout callout-info mr-tp-15 mr-bt-15" v-if="items.length < 1">
            No materials found for provided input.
         </div>
        <div class="table-responsive mr-tp-10" v-else>
            <table class="table table-striped table-bordered no-footer" id="ItemsTable">
                <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue">
                    <tr>
                        <th class="rate-text-center sno pd-10" width="4%">#</th>
                        <th class="rate-text-center">{{ headers[0] }}</th>
                        <th class="rate-text-center">{{ headers[1] }}</th>
                        <th class="rate-text-center">{{ headers[2] }}</th>
                        <th class="rate-text-center">{{ headers[3] }}</th>
                        <th class="pd-10 rate-text-center" width="12%">{{ headers[5] }}</th>
                    </tr>
                </thead>
               <tbody>
                   <tr v-for="(item, index) in items">
                       <td class="rate-text-center">{{ index+ 1 }}</td>  
                       <td class="text-vertical-align">{{ item.Brand }}</td>
                       <td class="text-vertical-align">{{ item.SubBrand }}</td>
                       <td class="text-vertical-align">{{ item.Name }}</td>
                       <td class="text-vertical-align" v-html="item.Number"></td>
                       <td class="rate-text-center">
                            <a :href="viewItemRoute+'/'+categorySlug+'/'+item.MaterialId"
                              data-toggle="tooltip" 
                              title="" 
                              class="cursor-pointer quick-view" 
                              data-original-title="Quick View"
                              :data-material-id="item.MaterialId"
                              >
                              <i class="fa fa-fw fa-eye"></i>
                           </a>
                            <a :href="viewItemRoute+'/'+categorySlug+'/'+item.MaterialId"
                              data-toggle="tooltip" 
                              title="" 
                              class="cursor-pointer view-material" 
                              data-original-title="Detailed View"
                              target="_blank"
                              >
                              <i class="fa fa-cart-plus"></i>
                           </a>
                            <a href="javascript:void(0)"
                              data-toggle="tooltip" 
                              title="" 
                              class="cursor-pointer recommend-material"
                              :data-original-title="isCustomer ? 'Select Material': 'Recommend Material'"
                              :data-material-id="item.MaterialId"
                              :data-api-end-point="selectionRoute"
                              >
                              <i class="fa fa-fw fa-plus-square"></i>
                            </a>
                            <a href="javascript:void(0)"
                              data-toggle="tooltip" 
                              title="" 
                              class="cursor-pointer finalize-material" 
                              :data-original-title="isCustomer ? 'Shortlist Material': 'Finalize Material'"
                              :data-material-id="item.MaterialId"
                              :data-api-end-point="finalizeRoute"
                              >
                              <i class="fa fa-fw fa-check-circle"></i>
                            </a>
                       </td>
                   </tr>
               </tbody>
            </table>
        </div>
    </div>
</template>

<script>
    // Child component
    export default {
        props: {
            "headers": {
                type: Array
            },
            "items": {
                type: Array
            }
        },
        data() {
            return {
                viewItemRoute: null,
                isCustomer: false,
                categorySlug: null,
                selectionRoute: null,
                finalizeRoute: null
            }
        },
        created() {
            this.viewItemRoute = this.$root.viewItemRoute;
            this.isCustomer = this.$root.isCustomer;
            this.categorySlug = this.$root.categorySlug;
            this.selectionRoute = this.$root.selectItemRoute;
            this.finalizeRoute = this.$root.finalizeItemRoute;
        }
    }
</script>