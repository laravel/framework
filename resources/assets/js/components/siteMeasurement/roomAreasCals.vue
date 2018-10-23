<template>
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="CalculationsTable">
            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                <tr>
                    <th class="text-center text-vertical-align" width="12%" rowspan="2">Room</th>
                    <th class="text-center text-vertical-align" width="7%" rowspan="2">Wall Area
                    <i class="fa fa-question-circle cursor-pointer help-icon" data-toggle="tooltip" data-original-title="After deducting area for windows & doors"></i>
                    </th>
                    <th class="text-center text-vertical-align" width="7%" rowspan="2">Ceiling Area</th>
                    <th class="text-center text-vertical-align" width="7%" rowspan="2">False ceiling area</th>
                    <th class="text-center text-vertical-align" width="14%" colspan="2">Paintable Wall Area</th>
                    <th class="text-center text-vertical-align" width="6%" rowspan="2">Window curtain rod</th>
                    <th class="text-center text-vertical-align" width="14%" colspan="2">Window curtain cloth</th>
                    <th class="text-center text-vertical-align" width="6%" rowspan="2">Door curtain rod</th>
                    <th class="text-center text-vertical-align" width="14%" colspan="2">Door curtain cloth</th>
                    <th class="text-center text-vertical-align" width="6%" rowspan="2">Grills</th>
                    <th class="text-center text-vertical-align" width="7%" rowspan="2">Mosquito mesh</th>
                </tr>
                <tr>
                    <th width="40%" class="text-center text-vertical-align">With False Ceiling</th>
                    <th width="60%" class="text-center text-vertical-align">Without False Ceiling</th>
                    <th width="40%" class="text-center text-vertical-align">Roman</th>
                    <th width="60%" class="text-center text-vertical-align">Eyelet/ American</th>
                    <th width="40%" class="text-center text-vertical-align">Roman</th>
                    <th width="60%" class="text-center text-vertical-align">Eyelet/ American</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="room in rooms.IndpendentAreas">
                    <td class="text-vertical-align" width="12%">{{room.Room}}</td>
                    <td class="text-center text-vertical-align" width="7%">{{room.WallArea}}</td>
                    <td class="text-center text-vertical-align" width="7%">{{room.CelingArea}}</td>
                    <td class="text-center text-vertical-align" width="7%">{{room.FalseCelngArea}}</td>                    
                    <td class="text-center text-vertical-align">{{room.paintablearea.withfc}}</td>
                    <td class="text-center text-vertical-align">{{room.paintablearea.widoutfc}}</td>
                    <td class="text-center text-vertical-align" width="6%">{{room.WindowCurRod}}</td>
                    <td class="text-center text-vertical-align">{{room.WindowCurtCloth.Roman}}</td>
                    <td class="text-center text-vertical-align">{{room.WindowCurtCloth.EYAM}}</td>
                    <td class="text-center text-vertical-align" width="6%">{{room.DoorCurtRod}}</td>
                    <td class="text-center text-vertical-align">{{room.DoorCurtCloth.Roman}}</td>
                    <td class="text-center text-vertical-align">{{room.DoorCurtCloth.EYAM}}</td>
                    <td class="text-center text-vertical-align" width="6%">{{room.Grills}}</td>
                    <td class="text-center text-vertical-align" width="7%">{{room.MosquitoMesh}}</td>
                </tr>
                <tr v-for="(area,index) in rooms.TotalAreas">
                    <td v-if="index === 0" class="text-vertical-align">
                        <strong>Total&nbsp;&nbsp;<i class="fa fa-fw fa-long-arrow-right"></i>
                        </strong>
                    </td>
                    <td class="text-center text-vertical-align">{{area.TotalWallArea}}</td>
                    <td class="text-center text-vertical-align">{{area.TotalCelingArea}}</td>
                    <td class="text-center text-vertical-align">{{area.TotalFalseClngArea}}</td>
                    <td class="text-center text-vertical-align">{{area.TotalPntAreaWithFC}}</td>
                    <td class="text-center text-vertical-align">{{area.TotalPntAreaWidtFC}}</td>
                    <td class="text-center text-vertical-align">{{area.TotalWindCurtrodArea}}</td>
                    <td class="text-center text-vertical-align">{{area.TotalWindowsRomancCurtCloth}}</td>
                    <td class="text-center text-vertical-align">{{area.TotalWindowsAmericanCurtCloth}}</td>
                    <td class="text-center text-vertical-align">{{area.TotalDoorsCurtRodArea}}</td>
                    <td class="text-center text-vertical-align">{{area.TotalDoorsRomanCurtCloth}}</td>
                    <td class="text-center text-vertical-align">{{area.TotalDoorsAmerCloth}}</td>
                    <td class="text-center text-vertical-align">{{area.TotalGrillsArea}}</td>
                    <td class="text-center text-vertical-align">{{area.TotalMosqMeshArea}}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
    export default {
        props: {
            "rooms": {
                type: Object
            }
        }
    }
</script>