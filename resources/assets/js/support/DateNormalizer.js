/**
 * Normalize given date to RFC2822 date format.
 *
 * @param  String  date
 * @return String
 */
export function normalize(date)
{
    let months = {
        "Jan": "01",
        "Feb": "02",
        "Mar": "03",
        "Apr": "04",
        "May": "05",
        "Jun": "06",
        "Jul": "07",
        "Aug": "08",
        "Sep": "09",
        "Oct": "10",
        "Nov": "11",
        "Dec": "12",
    },
    splittedDate = date.split("-");
    return `${splittedDate[2]}-${months[splittedDate[1]]}-${splittedDate[0]}`;
}
