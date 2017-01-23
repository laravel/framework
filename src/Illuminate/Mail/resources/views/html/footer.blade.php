<tr>
    <td>
        <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0">
            <tr>
                <td class="content-cell" align="center">
                    {{ app('mail.markdown')->parse($slot) }}
                </td>
            </tr>
        </table>
    </td>
</tr>
