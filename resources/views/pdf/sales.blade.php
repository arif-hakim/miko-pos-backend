<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales Report</title>
  <style>
    * {
      font-family: sans-serif;
    }
    .page-break {
        page-break-after: always;
    }
    table {
        page-break-inside: auto;
    }
    .custom-control-input:checked~.custom-control-label:before {
        border-color: #000 !important;
        background-color: #000 !important;
    }
    .textright-head {
        position: absolute;
        top:0;
        right:0;
        font-size: .85rem;
    }
    .bg-lightblue {
        background-color: #BCD5ED;
    }
    .d-flex {
      display: flex;
    }
    .d-flex > * {
      flex: 1;
    }
    .table {
      border-collapse: collapse;
      border: 1px solid black;
    }
    .table tr td, 
    .table tr th { 
      padding: .5em 
    }

    table td, table td * { vertical-align: top; }

    .table tr th { 
      background-color: lightblue;
    }
    
    .bg-success {
      background: #28c76f;
      color: #fff;
    }

    .bg-danger {
      background: #ea5455;
      color: #fff;
    }

    .bg-warning { 
      background: #ff9f43;
      color: #fff;
    }

    .bg-primary { 
      background: #7367f0;
      color: #fff;
    }

  </style>
</head>
<body>
  <h1>Sales Report</h1>
  <table>
    <tr>
      <td>Period</td>
      <td>: {{ date('d F Y', strtotime($period['from'])) }} - {{ date('d F Y', strtotime($period['to'])) }}</td>
    </tr>
    <tr>
      <td>Company</td>
      <td>: {{ $unit->branch->company->name }}</td>
    </tr>
    <tr>
      <td>Branch</td>
      <td>: {{ $unit->branch->name }}</td>
    </tr>
    <tr>
      <td>Unit</td>
      <td>: {{ $unit->name }}</td>
    </tr>
  </table>
  <hr>
  <table>
    <tr>
      <td>
        <div>
          <table>
            <tr>
              <td>Total Transactions</td>
              <td>: {{ $transactions->count() }}</td>
            </tr>
            <tr>
              <td>- Internal</td>
              <td>: {{ $transactions->where('payment_status', 'Internal')->count() }}</td>
            </tr>
            <tr>
              <td>- Completed</td>
              <td>: {{ $transactions->where('payment_status', 'Paid')->count() }}</td>
            </tr>
            <tr>
              <td>- Incomplete</td>
              <td>: {{ $transactions->where('payment_status', 'Unpaid')->count() }}</td>
            </tr>
            <tr>
              <td>- Canceled</td>
              <td>: {{ $transactions->where('payment_status', 'Canceled')->count() }}</td>
            </tr>
          </table>
        </div>
      </td>
      <td>
        <div style="margin-left: 350px;">
          <table>
            <tr>
              <td>Revenue</td>
              <td>: Rp. {{ number_format($revenue + $total_tax, 0, '.', ',') }}</td>
            </tr>
            <tr>
              <td>- Costs</td>
              <td>: Rp. {{ number_format($costs, 0, '.', ',') }}</td>
            </tr>
            <tr>
              <td>- Profit</td>
              <td>: Rp. {{ number_format($profit, 0, '.', ',') }}</td>
            </tr>
            <tr>
              <td>- Tax</td>
              <td>: Rp. {{ number_format($total_tax, 0, '.', ',') }}</td>
            </tr>
            <tr>
          </table>
        </div>
      </td>
    </tr>
  </table>
  <br>
  <table border="true" class="table">
    <thead>
      <th>#</th>
      <th>Date</th>
      <th>Customer Name</th>
      <th>Total</th>
      <th>Items</th>
      <th>Status</th>
      <th>Money Paid</th>
    </thead>
    @foreach($transactions as $transaction)
    <tr>
      <td>{{ $transaction->code }}</td>
      <td>{{ date('d M Y H:i' , strtotime($transaction->created_at)) }}</td>
      <td>{{ $transaction->employee_unit ? $transaction->employee_unit->name . ' - ' . $transaction->employee->firstname . ' '. $transaction->employee->lastname : $transaction->customer_name }}</td>
      <td>Rp. {{ number_format($transaction->grandtotal, 0, '.', ',') }}</td>
      <td>{{ $transaction->transaction_details->count() }}</td>
      @php
        $bg = '';
        if ($transaction->payment_status == 'Paid') $bg = 'bg-success';
        if ($transaction->payment_status == 'Canceled') $bg = 'bg-danger';
        if ($transaction->payment_status == 'Unpaid') $bg = 'bg-warning';
        if ($transaction->payment_status == 'Internal') $bg = 'bg-primary';
      @endphp
      <td class="{{ $bg }}">{{ $transaction->payment_status }}</td>
      <td>{{ $transaction->paid ? 'Rp.' . number_format($transaction->paid, 0, '.', ',') : '-' }}</td>
    </tr>
    @endforeach
  </table>
</body>
</html>