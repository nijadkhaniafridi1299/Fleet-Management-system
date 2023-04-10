@if ($demo->preferred_communication_id == 'en')
<br/>
Order ID: <b> {{ $demo->order_id }}</b>.
<br/>
Order Date: <b> {{ $demo->order_date }}</b>.
<br/>
<p>{{$demo->msg}}</p>
@else
<br/>
<div style="text-align:right;">
.<b> {{ $demo->order_id }}</b> رقم التعريف الخاص بالطلب
<br/>
<b> {{ $demo->order_date }}</b>: تاريخ الطلب
<br/>
<p>{{$demo->msg}}</p>
<div/>
@endif
