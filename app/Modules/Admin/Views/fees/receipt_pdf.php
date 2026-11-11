<?php
/**
 * @var array  $payment
 * @var array  $tenant
 * @var array  $branding
 * @var array  $allocations
 * @var object|null $receivedBy
 * @var float  $totalPaid
 */
$primary = $branding['primary_color'] ?? '#4f46e5';
$footer  = $branding['receipt_footer'] ?? 'Thank you for your payment.';
$tenantName = $tenant['name'] ?? 'CoachDesk Institute';
$tenantEmail = $tenant['email'] ?? '';
$tenantPhone = $tenant['phone'] ?? '';
// $tenantAddr  = trim(implode(', ', array_filter([
//     $tenant['address_line1'] ?? '',
//     $tenant['city'] ?? '',
//     $tenant['state'] ?? '',
//     $tenant['pincode'] ?? '',
// ])));

$tenantAddr = trim(implode(', ', array_filter(array(
    isset($tenant['address_line1']) ? $tenant['address_line1'] : '',
    isset($tenant['city']) ? $tenant['city'] : '',
    isset($tenant['state']) ? $tenant['state'] : '',
    isset($tenant['pincode']) ? $tenant['pincode'] : ''
))));

$methodLabel = ucfirst(str_replace('_', ' ', $payment['method']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt - <?php echo esc($payment['receipt_number']) ?></title>
<style>
  /* DomPDF — keep CSS simple, avoid flex/grid */
  @page { margin: 30px 40px; }
  body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 0; }
  table { border-collapse: collapse; width: 100%; }
  .header-table td { vertical-align: top; }
  .brand-name { font-size: 22px; font-weight: bold; color: <?php echo $primary ?>; }
  .brand-sub  { font-size: 11px; color: #6b7280; margin-top: 2px; }
  .receipt-title { font-size: 24px; font-weight: bold; color: <?php echo $primary ?>; text-align: right; }
  .receipt-meta  { text-align: right; font-size: 11px; color: #4b5563; margin-top: 4px; }
  .divider { height: 2px; background: <?php echo $primary ?>; margin: 16px 0 24px; }

  .info-box {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 12px 14px;
    font-size: 11px;
    color: #374151;
    line-height: 1.5;
  }
  .info-box .label { color: #6b7280; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
  .info-box .value { color: #111827; font-weight: bold; font-size: 12px; }

  .section-title {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #6b7280;
    margin: 24px 0 8px 0;
    font-weight: bold;
  }

  .items-table th {
    background: #f3f4f6;
    padding: 8px 10px;
    text-align: left;
    font-size: 11px;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
  }
  .items-table td {
    padding: 8px 10px;
    font-size: 11px;
    color: #1f2937;
    border-bottom: 1px solid #f3f4f6;
  }
  .items-table .num { text-align: right; }

  .total-box {
    background: <?php echo $primary ?>;
    color: #ffffff;
    padding: 14px 18px;
    border-radius: 6px;
    margin-top: 16px;
  }
  .total-box .label { font-size: 11px; opacity: 0.85; text-transform: uppercase; letter-spacing: 1px; }
  .total-box .amount { font-size: 24px; font-weight: bold; margin-top: 2px; }

  .footer {
    margin-top: 40px;
    padding-top: 16px;
    border-top: 1px dashed #d1d5db;
    font-size: 10px;
    color: #6b7280;
    text-align: center;
    line-height: 1.6;
  }
  .footer strong { color: #374151; }
  .status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: bold;
    background: #dcfce7;
    color: #166534;
    text-transform: uppercase;
  }
</style>
</head>
<body>

<!-- HEADER -->
<table class="header-table">
  <tr>
    <td style="width:60%;">
      <div class="brand-name"><?php echo esc($tenantName) ?></div>
      <?php if ($tenantAddr): ?>
        <div class="brand-sub"><?php echo esc($tenantAddr) ?></div>
      <?php endif; ?>
      <div class="brand-sub">
        <?php if ($tenantPhone): ?>📞 <?php echo esc($tenantPhone) ?><?php endif; ?>
        <?php if ($tenantEmail): ?> &nbsp;·&nbsp; ✉ <?php echo esc($tenantEmail) ?><?php endif; ?>
      </div>
    </td>
    <td style="width:40%;">
      <div class="receipt-title">RECEIPT</div>
      <div class="receipt-meta">
        <strong><?php echo esc($payment['receipt_number']) ?></strong><br>
        <?php echo date('d M Y', strtotime($payment['payment_date'])) ?>
      </div>
    </td>
  </tr>
</table>

<div class="divider"></div>

<!-- PAID TO / BY -->
<table>
  <tr>
    <td style="width:50%; padding-right:8px;">
      <div class="info-box">
        <div class="label">Received From</div>
        <div class="value"><?php echo esc($payment['student_name']) ?></div>
        <div style="margin-top:4px;">
          <strong><?php echo esc($payment['student_code']) ?></strong>
          <?php if (! empty($payment['student_phone'])): ?>
            <br>📞 <?php echo esc($payment['student_phone']) ?>
          <?php endif; ?>
          <?php if (! empty($payment['student_email'])): ?>
            <br>✉ <?php echo esc($payment['student_email']) ?>
          <?php endif; ?>
        </div>
      </div>
    </td>
    <td style="width:50%; padding-left:8px;">
      <div class="info-box">
        <div class="label">Payment Details</div>
        <table style="font-size:11px;">
          <tr>
            <td style="color:#6b7280; padding:2px 0;">Method</td>
            <td style="text-align:right; color:#111827; font-weight:bold;"><?php echo esc($methodLabel) ?></td>
          </tr>
          <tr>
            <td style="color:#6b7280; padding:2px 0;">Date</td>
            <td style="text-align:right;"><?php echo date('d M Y', strtotime($payment['payment_date'])) ?></td>
          </tr>
          <tr>
            <td style="color:#6b7280; padding:2px 0;">Status</td>
            <td style="text-align:right;"><span class="status-badge"><?php echo esc($payment['status']) ?></span></td>
          </tr>
          <?php if ($receivedBy): ?>
          <tr>
            <td style="color:#6b7280; padding:2px 0;">Received by</td>
            <td style="text-align:right;"><?php echo esc($receivedBy->name) ?></td>
          </tr>
          <?php endif; ?>
        </table>
      </div>
    </td>
  </tr>
</table>

<!-- ALLOCATIONS (what this payment covered) -->
<?php if (! empty($allocations)): ?>
<div class="section-title">Payment Applied To</div>
<table class="items-table">
  <thead>
    <tr>
      <th style="width:60%;">Installment</th>
      <th style="width:20%;">Due Date</th>
      <th style="width:20%;" class="num">Amount</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($allocations as $a): ?>
      <tr>
        <td><?php echo esc($a['installment_name']) ?></td>
        <td><?php echo $a['due_date'] ? date('d M Y', strtotime($a['due_date'])) : '—' ?></td>
        <td class="num"><?php echo money($a['allocated_amount']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<!-- TOTAL -->
<table style="margin-top:16px;">
  <tr>
    <td style="width:60%;"></td>
    <td style="width:40%;">
      <div class="total-box">
        <div class="label">Amount Paid</div>
        <div class="amount"><?php echo money($payment['amount']) ?></div>
      </div>
    </td>
  </tr>
</table>

<?php if (! empty($payment['notes'])): ?>
<div class="section-title">Notes</div>
<div class="info-box" style="font-style:italic;">
  <?php echo nl2br(esc($payment['notes'])) ?>
</div>
<?php endif; ?>

<!-- SUMMARY -->
<div style="margin-top:24px; padding:12px 14px; background:#eef2ff; border-left:4px solid <?php echo $primary ?>; border-radius:4px;">
  <table style="font-size:11px;">
    <tr>
      <td style="color:#4338ca; padding:2px 0;">Total paid by this student to date</td>
      <td style="text-align:right; color:#4338ca; font-weight:bold;"><?php echo money($totalPaid) ?></td>
    </tr>
  </table>
</div>

<!-- FOOTER -->
<div class="footer">
  <strong><?php echo esc($footer) ?></strong><br>
  This is a computer-generated receipt and does not require a signature.<br>
  Generated on <?php echo date('d M Y, h:i A') ?> · <?php echo esc($tenantName) ?>
</div>

</body>
</html>