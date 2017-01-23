<table class="panel" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td class="panel-content">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="panel-item">
                        {{ app('mail.markdown')->parse($slot) }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
