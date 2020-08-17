<?php
class AjaxSubmit
{
    public $data;
    public $conversion_rate;
    public function __construct($files)
    {
        $this->data = $files;
        $this->conversion_rate = $this->get_conversion_rate();
    }
    public function init()
    {
        $files = $this->data;
        $responce = array();
        if (!empty($_FILES['csv_file']['name'])) {
            $file_data = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $csv_data = $this->process_csv($file_data);
            $responce['data'] = $csv_data;
            $responce['responce'] = 'Success';
        } else {
            $responce['responce'] = 'Error Something Went Wrong';
        }
        return json_encode($responce);
    }
    protected function formulas($row_data)
    {
        $table_rows = array();
        foreach ($row_data as $key=>$val) {
            $price = (empty($val['price'])) ? 0 : $val['price'];
            $cost = (empty($val['cost'])) ? 0 : $val['cost'];
            $qty = $val['qty'];
            if (empty($qty)) {
                $qty = 0;
            } elseif ($qty < 0) {
                $qty = abs($qty);
            }
            $val['price'] = '$'.number_format($price, 2);
            $val['cost'] = '$'.number_format($cost, 2);
            $profit_margin = ($price - $cost) * $qty;
            $val['profit_margin'] = '$'.number_format($profit_margin, 2);
            $total_profit = ($price * $qty) - ($cost * $qty);
            $total_cad = $this->canadian_convert($total_profit);
            $val['total_profit_usd'] = '$'.number_format($total_profit, 2);
            $val['total_profit_cad'] = '$'.number_format($total_cad, 2);
            $table_rows[] = $val;
        }
        $nodata = (empty($table_rows)) ? true: false;
        $average_price = ($nodata) ? 0 : $this->do_math('average', $table_rows, 'price');
        $average_profit_margin = ($nodata) ? 0 : $this->do_math('average', $table_rows, 'profit_margin');
        $total_qty = ($nodata) ? 0 : $this->do_math('sum', $table_rows, 'qty');
        $total_profit_usd = ($nodata) ? 0 : $this->do_math('sum', $table_rows, 'total_profit_usd');
        $total_profit_cad = ($nodata) ? 0 : $this->do_math('sum', $table_rows, 'total_profit_cad');
        $table_vals = array(
              'row_data'  => $table_rows,
              'average_price'  => $average_price,
              'average_profit_margin'  => $average_profit_margin,
              'total_qty'  => $total_qty,
              'total_profit_usd'  => $total_profit_usd,
              'total_profit_cad'  => $total_profit_cad,
         );
        return $table_vals;
    }
    protected function do_math($type, $info, $column)
    {
        $replace = array("$",",");
        $column = str_replace($replace, "", array_column($info, $column));
        $total = '';
        switch ($type) {
          case "sum":
            $total =  array_sum($column);
            break;
          case "average":
            $total =  array_sum($column)/count($column);
            break;
          default:
        }
        if ($total == 0 || $total == '0') {
            return '$0';
        } else {
            return '$'.number_format($total, 2);
        }
    }
    protected function process_csv($file_data)
    {
        $new_csv = array();
        $row_data = array();
        $columns = fgetcsv($file_data);
        while ($row = fgetcsv($file_data)) {
            $row_data[] = array_combine($columns, $row);
        }
        $new_csv['rows'] = $this->formulas($row_data);
        $new_csv['columns'] = $this->create_headers($columns);
        return $new_csv;
    }
    protected function check_val($amount)
    {
        $style = ($amount < 0) ? 'negative-value' : 'positive-value';
        return '<span class="'.$style.'">'.$amount.'</span>';
    }
    protected function canadian_convert($amount)
    {
        $replace = array("$",",");
        $amount = str_replace($replace, "", $amount);
        $conversion_rate = $this->conversion_rate;
        $converted_amount = round($amount / $conversion_rate, 2);
        return $converted_amount;
    }
    protected function get_conversion_rate()
    {
        $url = "http://data.fixer.io/api/latest?access_key=6ba44ae6c20fdb1ecaf3653c922f2a21&symbols=USD,CAD&format=1";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        $data = json_decode($result);
        $conversion_rate  = $data->rates->USD / $data->rates->CAD;
        return $conversion_rate;
    }
    protected function create_headers($column)
    {
        $column_new = array();
        $column_new['sku'] = 'Sku';
        $column_new['price'] = 'Price';
        $column_new['qty'] = 'Qty';
        $column_new['cost'] = 'Cost';
        $column_new['profit_margin'] = 'Profit Margin';
        $column_new['total_profit_usd'] = 'Total Profit (USD)';
        $column_new['total_profit_cad'] = 'Total Profit (CAD)';
        return $column_new;
    }
}
$ajax = new AjaxSubmit($_FILES);
$output = $ajax->init();
echo $output;
