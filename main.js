var $themescript = jQuery.noConflict();
$themescript(function($) {

    $('#currency-form').on('submit', function(event) {
        event.preventDefault();

        $.ajax({
            url: "ajax.php",
            method: "POST",
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            success: function(res) {

                if (res.responce == "Success") {

                    $('#converted_data').html('');

                    var table_data = res.data.rows;
                    var table_rows = res.data.rows.row_data
                    var columns = res.data.columns;

                    var head = '<thead><tr>';
                    for (var key in columns) {
                        if (columns.hasOwnProperty(key)) {
                            //console.log(key + " -> " + columns[key]);
                            head += '<th>' + columns[key] + '</th>';
                        }
                    }
                    $('#converted_data').append(head + '</tr></thead>');

                    var body = '<tbody>';
                    table_rows.forEach(function(obj) {
                        var row = '<tr>';

                        for (var key in columns) {
                            //if(obj.hasOwnProperty(key)) {
                            if (key == 'qty' || key == 'profit_margin' || key == 'total_profit_cad' || key == 'total_profit_usd') {
                                var total_value = obj[key];
                                total_value = total_value.toString();
                                var price = total_value.replace(/\$|,/g, '')
                                var convertedToNumber = parseFloat(price);
                                style_value = (convertedToNumber < 0) ? 'negative-value' : 'positive-value';
                                row += '<td class="' + style_value + '">';
                            } else {
                                row += '<td>';
                            }
                            row += obj[key];
                            //}
                            row += '</td>';
                        };
                        body += row + '<tr>';
                    })
                    $('#converted_data').append(body + '</tbody>');

                    var tfoot = '<tfoot class="tfoot">';
                    tfoot += '<tr><td colspan="5"></td><td>Average Price</td><td><span>' + table_data.average_price + '</span></td><tr>';
                    tfoot += '<tr><td colspan="5"></td><td>Total Qty</td><td><span>' + table_data.total_qty + '</span></td><tr>';
                    tfoot += '<tr><td colspan="5"></td><td>Average profit margin</td><td><span>' + table_data.average_profit_margin + '</span></td><tr>';
                    tfoot += '<tr><td colspan="5"></td><td>Total Profit (USD)</td><td><span>' + table_data.total_profit_usd + '</span></td><tr>';
                    tfoot += '<tr><td colspan="5"></td><td>Total Profit (CAD)</td><td><span>' + table_data.total_profit_cad + '</span></td><tr>';
                    $('#converted_data').append(tfoot + '</tfoot>');


                } else {
                    alert('We had trouble reading your csv, try again!');
                }
                $('#currency-form')[0].reset();
                $('#csv_file').attr('placeholder', 'Drag and drop a file');
            },
            error: function(error) {
                console.log(error); // For testing (to be removed)
            }
        });

    })
    return false;
});

function readUrl(input) {

    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = (e) => {
            let imgData = e.target.result;
            let imgName = input.files[0].name;
            input.setAttribute("data-title", imgName);
            console.log(e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }

}
