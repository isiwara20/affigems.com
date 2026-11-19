<?php
/**
 * ARIGATO.LK — professional PDF invoice generator (FPDF).
 * Layout matches helpers/invoice.php HTML preview (admin template).
 */

require_once __DIR__ . '/../libs/FPDF/fpdf.php';

class InvoicePdfGenerator extends FPDF
{
    private array $settings;
    private array $accentRgb;
    private array $secondaryRgb;
    private float $angle = 0;

    public function __construct(array $settings)
    {
        parent::__construct('P', 'mm', 'A4');
        $this->settings = $settings;
        $this->accentRgb = lumak_invoice_hex_to_rgb($settings['accent_color'] ?? '#002868');
        $this->secondaryRgb = lumak_invoice_hex_to_rgb($settings['secondary_color'] ?? '#003b94');
        $this->SetAutoPageBreak(true, 22);
        $this->SetMargins(15, 15, 15);
    }

    private function encode(string $text): string
    {
        if ($this->iconv) {
            $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
            return $converted !== false ? $converted : $text;
        }
        return $text;
    }

    private function setAccentFill(): void
    {
        $this->SetFillColor($this->accentRgb[0], $this->accentRgb[1], $this->accentRgb[2]);
    }

    private function setAccentText(): void
    {
        $this->SetTextColor($this->accentRgb[0], $this->accentRgb[1], $this->accentRgb[2]);
    }

    private function setLightPurpleFill(): void
    {
        $this->SetFillColor(250, 246, 255);
    }

    private function setTableHeaderFill(): void
    {
        $this->SetFillColor(243, 236, 255);
    }

    private function setBorderColor(): void
    {
        $this->SetDrawColor(232, 220, 248);
    }

    private function rotate(float $angle, float $x = -1, float $y = -1): void
    {
        if ($x === -1) {
            $x = $this->x;
        }
        if ($y === -1) {
            $y = $this->y;
        }
        if ($this->angle !== 0) {
            $this->_out('Q');
        }
        $this->angle = $angle;
        if ($angle != 0) {
            $angleRad = $angle * M_PI / 180;
            $c = cos($angleRad);
            $s = sin($angleRad);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                $c,
                $s,
                -$s,
                $c,
                $cx,
                $cy,
                -$cx,
                -$cy
            ));
        }
    }

    public function build(array $order, array $items): void
    {
        $this->AddPage();
        $this->drawHeader();
        $this->drawMeta($order);
        $this->drawAddresses($order);
        $this->drawItemsTable($items);
        $totalsPos = $this->drawTotals($order);
        $this->SetY($totalsPos['afterY']);

        if (lumak_invoice_is_paid($order)) {
            $position = $this->settings['paid_seal_position'] ?? 'over_totals';
            if ($position === 'over_totals') {
                $this->drawPaidSeal($order, $totalsPos['grandY']);
                $this->SetY(max($this->GetY(), $totalsPos['afterY'] + 22));
            } else {
                $this->drawPaidSeal($order, null);
                $this->Ln(6);
            }
        } else {
            $this->Ln(8);
        }

        $this->drawFooterBlocks();
    }

    private function drawHeader(): void
    {
        // Gradient-style header (accent -> secondary)
        $this->setAccentFill();
        $this->Rect(0, 0, 105, 32, 'F');
        $this->SetFillColor($this->secondaryRgb[0], $this->secondaryRgb[1], $this->secondaryRgb[2]);
        $this->Rect(105, 0, 105, 32, 'F');

        // Company info — left (matches HTML .brand)
        $this->SetXY(15, 9);
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(255, 255, 255);
        $defaultCompany = defined('STORE_OWNER') ? STORE_OWNER : (defined('SITE_NAME') ? SITE_NAME : 'Affinity Gems and Jewels Pvt Ltd');
        $this->Cell(120, 7, $this->encode($this->settings['company_name'] ?? $defaultCompany), 0, 1);

        $this->SetX(15);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(235, 225, 255);
        $tagline = $this->settings['company_tagline'] ?? '';
        if ($tagline !== '') {
            $this->Cell(120, 4, $this->encode($tagline), 0, 1);
        }

        $contact = trim(implode(' | ', array_filter([
            $this->settings['company_address'] ?? '',
            $this->settings['company_phone'] ?? '',
            $this->settings['company_email'] ?? '',
            $this->settings['company_website'] ?? '',
        ])));
        if ($contact !== '') {
            $this->SetX(15);
            $this->MultiCell(120, 3.5, $this->encode($contact), 0, 'L');
        }

        // Logo — right (matches HTML .logo on right side)
        if (($this->settings['show_logo'] ?? '1') === '1') {
            $logoPath = lumak_invoice_logo_path();
            if ($logoPath) {
                try {
                    $this->Image($logoPath, 162, 8, 33);
                } catch (\Throwable $e) {
                    // skip logo
                }
            }
        }

        $this->SetY(38);
    }

    private function drawMeta(array $order): void
    {
        $invoiceNo = lumak_invoice_number($order, $this->settings);
        $y = $this->GetY();

        // Title — left
        $this->SetFont('Arial', 'B', 22);
        $this->setAccentText();
        $this->SetXY(15, $y);
        $this->Cell(100, 10, $this->encode($this->settings['invoice_title'] ?? 'INVOICE'), 0, 0);

        // Meta — right (matches HTML .meta)
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(80, 80, 80);
        $metaX = 120;
        $metaLines = [
            ['Invoice:', $invoiceNo],
            ['Order:', '#' . ($order['order_number'] ?? '')],
            ['Date:', date('d M Y', strtotime($order['created_at'] ?? 'now'))],
            ['Status:', ucfirst($order['order_status'] ?? 'pending')],
        ];
        $lineY = $y;
        foreach ($metaLines as $line) {
            $this->SetXY($metaX, $lineY);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(18, 5, $this->encode($line[0]), 0, 0, 'R');
            $this->SetFont('Arial', '', 9);
            $this->Cell(0, 5, $this->encode($line[1]), 0, 1, 'R');
            $lineY += 5;
        }

        $this->SetY(max($y + 12, $lineY) + 4);
    }

    private function drawAddresses(array $order): void
    {
        $y = $this->GetY();
        $boxH = 36;
        $this->setBorderColor();
        $this->setLightPurpleFill();
        $this->Rect(15, $y, 88, $boxH, 'DF');
        $this->Rect(107, $y, 88, $boxH, 'DF');

        // Bill To
        $this->SetXY(18, $y + 3);
        $this->SetFont('Arial', 'B', 8);
        $this->setAccentText();
        $this->Cell(0, 4, $this->encode('BILL TO'), 0, 1);

        $this->SetX(18);
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(34, 34, 34);
        $this->Cell(82, 5, $this->encode($order['full_name'] ?? ''), 0, 1);

        $this->SetX(18);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(85, 85, 85);
        $lines = array_filter([
            $order['email'] ?? '',
            $order['phone'] ?? '',
            trim(($order['address_1'] ?? '') . ($order['address_2'] ? ', ' . $order['address_2'] : '')),
            trim(($order['city'] ?? '') . ', ' . ($order['district'] ?? '')),
            $order['postal_code'] ?? '',
        ]);
        foreach ($lines as $line) {
            $this->SetX(18);
            $this->Cell(82, 3.8, $this->encode($line), 0, 1);
        }

        // Invoice Details
        $this->SetXY(110, $y + 3);
        $this->SetFont('Arial', 'B', 8);
        $this->setAccentText();
        $this->Cell(0, 4, $this->encode('INVOICE DETAILS'), 0, 1);

        $isPaid = lumak_invoice_is_paid($order);
        $this->SetX(110);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(85, 85, 85);
        $this->Cell(28, 4.5, $this->encode('Payment:'), 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor(34, 34, 34);
        $this->Cell(0, 4.5, $this->encode(lumak_invoice_payment_label($order['payment_method'] ?? 'cod')), 0, 1);

        $this->SetX(110);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(85, 85, 85);
        $this->Cell(28, 4.5, $this->encode('Payment Status:'), 0, 0);
        $this->SetFont('Arial', 'B', 8);
        if ($isPaid) {
            $this->SetTextColor(22, 163, 74);
            $this->Cell(0, 4.5, $this->encode('Paid'), 0, 1);
        } else {
            $this->SetTextColor(34, 34, 34);
            $this->Cell(0, 4.5, $this->encode(ucfirst($order['payment_status'] ?? 'pending')), 0, 1);
        }

        $this->SetX(110);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(85, 85, 85);
        $this->Cell(28, 4.5, $this->encode('Delivery:'), 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor(34, 34, 34);
        $this->Cell(0, 4.5, $this->encode(ucfirst($order['delivery_method'] ?? 'standard')), 0, 1);

        $this->SetY($y + $boxH + 4);
    }

    private function drawItemsTable(array $items): void
    {
        $this->setBorderColor();
        $this->setTableHeaderFill();
        $this->setAccentText();
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(10, 8, '#', 1, 0, 'C', true);
        $this->Cell(78, 8, $this->encode('DESCRIPTION'), 1, 0, 'L', true);
        $this->Cell(18, 8, $this->encode('QTY'), 1, 0, 'C', true);
        $this->Cell(34, 8, $this->encode('UNIT PRICE'), 1, 0, 'R', true);
        $this->Cell(35, 8, $this->encode('AMOUNT'), 1, 1, 'R', true);

        $this->SetFont('Arial', '', 8);
        $i = 1;
        foreach ($items as $item) {
            $name = $item['product_name'] ?? $item['name'] ?? 'Item';
            if (!empty($item['variation_label'])) {
                $name .= ' (' . $item['variation_label'] . ')';
            }
            $serialLabel = lumak_invoice_item_serial_label($item);
            if ($serialLabel !== '') {
                $name .= ' | ' . $serialLabel;
            }
            if ($i % 2 === 0) {
                $this->setLightPurpleFill();
            } else {
                $this->SetFillColor(255, 255, 255);
            }
            $this->SetTextColor(50, 50, 50);
            $this->SetDrawColor(240, 232, 255);
            $this->Cell(10, 7, (string)$i, 'B', 0, 'C', true);
            $this->Cell(78, 7, $this->encode($name), 'B', 0, 'L', true);
            $this->Cell(18, 7, (string)(int)($item['quantity'] ?? 1), 'B', 0, 'C', true);
            $this->Cell(34, 7, $this->encode('LKR ' . number_format((float)($item['price'] ?? 0), 2)), 'B', 0, 'R', true);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(35, 7, $this->encode('LKR ' . number_format((float)($item['total'] ?? 0), 2)), 'B', 1, 'R', true);
            $this->SetFont('Arial', '', 8);
            $i++;
        }
        $this->setBorderColor();
        $this->Ln(4);
    }

    /**
     * @return array{afterY: float, grandY: float}
     */
    private function drawTotals(array $order): array
    {
        $x = 120;
        $w = 75;
        $this->SetX($x);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(80, 80, 80);

        $rows = [
            ['Subtotal', 'LKR ' . number_format((float)($order['subtotal'] ?? 0), 2)],
        ];
        if ((float)($order['discount'] ?? 0) > 0) {
            $label = 'Discount';
            if (!empty($order['coupon_code'])) {
                $label .= ' (' . $order['coupon_code'] . ')';
            }
            $rows[] = [$label, '- LKR ' . number_format((float)$order['discount'], 2)];
        }
        $delivery = (float)($order['delivery_fee'] ?? 0);
        $rows[] = ['Delivery', $delivery <= 0 ? 'FREE' : 'LKR ' . number_format($delivery, 2)];

        $taxRate = (float)($this->settings['tax_rate'] ?? 0);
        if ($taxRate > 0) {
            $taxAmount = ((float)($order['subtotal'] ?? 0) - (float)($order['discount'] ?? 0)) * ($taxRate / 100);
            $taxLabel = ($this->settings['tax_label'] ?? 'Tax') . ' (' . $taxRate . '%)';
            $rows[] = [$taxLabel, 'LKR ' . number_format($taxAmount, 2)];
        }

        foreach ($rows as $row) {
            $this->SetX($x);
            $this->Cell($w - 35, 5, $this->encode($row[0]), 0, 0);
            $this->Cell(35, 5, $this->encode($row[1]), 0, 1, 'R');
            $this->SetDrawColor(220, 220, 220);
            $this->SetLineWidth(0.1);
            $this->Line($x, $this->GetY(), $x + $w, $this->GetY());
            $this->Ln(1);
        }

        // Grand total bar (matches HTML .total-line.grand)
        $this->Ln(2);
        $grandY = $this->GetY();
        if (lumak_invoice_is_paid($order)) {
            $this->SetFillColor(22, 163, 74);
        } else {
            $this->setAccentFill();
        }
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        $this->SetX($x);
        $this->RoundedRect($x, $grandY, $w, 10, 2, 'F');
        $this->SetXY($x + 3, $grandY + 2.5);
        $this->Cell($w - 40, 5, $this->encode(lumak_invoice_total_label($order)), 0, 0);
        $this->Cell(32, 5, $this->encode('LKR ' . number_format((float)($order['total'] ?? 0), 2)), 0, 1, 'R');
        $afterY = $grandY + 14;
        $this->SetY($afterY);

        return [
            'afterY' => $afterY,
            'grandY' => $grandY + 5,
        ];
    }

    private function RoundedRect(float $x, float $y, float $w, float $h, float $r, string $style = ''): void
    {
        $k = $this->k;
        $hp = $this->h;
        if ($style === 'F') {
            $op = 'f';
        } elseif ($style === 'FD' || $style === 'DF') {
            $op = 'B';
        } else {
            $op = 'S';
        }
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $this->_out(sprintf('%.2F %.2F l', ($x + $w - $r) * $k, ($hp - $y) * $k));
        $this->_Arc($x + $w - $r + $r * $MyArc, $y, $x + $w, $y + $r - $r * $MyArc, $x + $w, $y + $r);
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - ($y + $h - $r)) * $k));
        $this->_Arc($x + $w, $y + $h - $r + $r * $MyArc, $x + $w - $r + $r * $MyArc, $y + $h, $x + $w - $r, $y + $h);
        $this->_out(sprintf('%.2F %.2F l', ($x + $r) * $k, ($hp - ($y + $h)) * $k));
        $this->_Arc($x + $r - $r * $MyArc, $y + $h, $x, $y + $h - $r + $r * $MyArc, $x, $y + $h - $r);
        $this->_out(sprintf('%.2F %.2F l', ($x) * $k, ($hp - ($y + $r)) * $k));
        $this->_Arc($x, $y + $r - $r * $MyArc, $x + $r - $r * $MyArc, $y, $x + $r, $y);
        $this->_out($op);
    }

    private function _Arc(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
    {
        $h = $this->h;
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            $x1 * $this->k,
            ($h - $y1) * $this->k,
            $x2 * $this->k,
            ($h - $y2) * $this->k,
            $x3 * $this->k,
            ($h - $y3) * $this->k
        ));
    }

    private function drawPaidSeal(array $order, ?float $anchorY = null): void
    {
        $position = $this->settings['paid_seal_position'] ?? 'over_totals';
        $coords = lumak_invoice_paid_seal_coords($this->settings);
        if ($anchorY !== null && $position === 'over_totals') {
            $sealX = 157 + (int)($this->settings['paid_seal_offset_x'] ?? 0);
            $sealY = $anchorY + (int)($this->settings['paid_seal_offset_y'] ?? 0);
            $rotation = (float)($this->settings['paid_seal_rotation'] ?? -14);
        } else {
            $sealX = $coords['x'];
            $sealY = $coords['y'];
            $rotation = $coords['rotation'];
        }
        $paidDate = lumak_invoice_paid_date($order);

        $this->rotate($rotation, $sealX, $sealY);

        // Double-border stamp (matches HTML .paid-seal-inner)
        $this->SetFillColor(236, 253, 245);
        $this->SetDrawColor(22, 163, 74);
        $this->SetLineWidth(1.0);
        $this->Rect($sealX - 28, $sealY - 14, 56, 32, 'DF');
        $this->SetLineWidth(0.5);
        $this->Rect($sealX - 26, $sealY - 12, 52, 28, 'D');

        $this->SetXY($sealX - 28, $sealY - 2);
        $this->SetFont('Arial', 'B', 28);
        $this->SetTextColor(22, 163, 74);
        $this->Cell(56, 10, $this->encode('PAID'), 0, 1, 'C');

        $this->SetX($sealX - 28);
        $this->SetFont('Arial', 'B', 6);
        $this->SetTextColor(21, 128, 61);
        $this->Cell(56, 4, $this->encode('PAYMENT RECEIVED'), 0, 1, 'C');

        $this->SetX($sealX - 28);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(22, 101, 52);
        $this->Cell(56, 4, $this->encode($paidDate), 0, 1, 'C');

        $this->rotate(0);
    }

    private function drawFooterBlocks(): void
    {
        $paymentNote = trim($this->settings['payment_note'] ?? '');
        $terms = trim($this->settings['terms_text'] ?? '');
        $footer = trim($this->settings['footer_note'] ?? '');

        $this->Ln(4);

        if ($paymentNote !== '') {
            $this->SetFont('Arial', 'B', 9);
            $this->setAccentText();
            $this->Cell(0, 5, $this->encode('Payment Information'), 0, 1);
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor(90, 90, 90);
            $this->MultiCell(0, 4, $this->encode($paymentNote));
            $this->Ln(2);
        }

        if ($terms !== '') {
            $this->SetFont('Arial', 'B', 9);
            $this->setAccentText();
            $this->Cell(0, 5, $this->encode('Terms & Conditions'), 0, 1);
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor(90, 90, 90);
            $this->MultiCell(0, 4, $this->encode($terms));
            $this->Ln(2);
        }

        if ($footer !== '') {
            $this->SetDrawColor($this->secondaryRgb[0], $this->secondaryRgb[1], $this->secondaryRgb[2]);
            $this->Line(15, $this->GetY(), 195, $this->GetY());
            $this->Ln(3);
            $this->SetFont('Arial', 'I', 9);
            $this->SetTextColor(100, 100, 100);
            $this->MultiCell(0, 4, $this->encode($footer), 0, 'C');
        }
    }
}
