<div class="admin-ship-overlay" id="adminShipOverlay" hidden>
    <div class="admin-ship-modal" role="dialog" aria-modal="true" aria-labelledby="adminShipTitle">
        <div class="admin-ship-head">
            <div class="admin-ship-icon"><i class="fas fa-truck"></i></div>
            <h3 id="adminShipTitle">Shipping Details</h3>
            <p>Enter tracking information before marking this order as shipped.</p>
        </div>
        <form id="adminShipForm" class="admin-ship-form">
            <input type="hidden" id="adminShipOrderId" name="order_id" value="">
            <div class="admin-ship-field">
                <label for="adminShipCourier">Courier Company <span>*</span></label>
                <input type="text" id="adminShipCourier" name="courier_company" list="adminCourierList" placeholder="e.g. SL Post, Domex, Pronto" required>
                <datalist id="adminCourierList">
                    <option value="SL Post"></option>
                    <option value="Domex"></option>
                    <option value="Pronto"></option>
                    <option value="Citypak"></option>
                    <option value="Aramex"></option>
                    <option value="DHL"></option>
                    <option value="FedEx"></option>
                    <option value="UPS"></option>
                </datalist>
            </div>
            <div class="admin-ship-field">
                <label for="adminShipTracking">Tracking ID <span>*</span></label>
                <input type="text" id="adminShipTracking" name="tracking_id" placeholder="Enter tracking / reference number" required>
            </div>
            <p class="admin-ship-note"><i class="fas fa-info-circle"></i> The customer will receive an email and SMS with these details.</p>
            <div class="admin-ship-actions">
                <button type="button" class="admin-ship-btn secondary" id="adminShipCancel">Cancel</button>
                <button type="submit" class="admin-ship-btn primary" id="adminShipSubmit">
                    <i class="fas fa-paper-plane"></i> Mark as Shipped
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.admin-ship-overlay {
    position: fixed;
    inset: 0;
    z-index: 12000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(13, 17, 55, 0.55);
    backdrop-filter: blur(4px);
}
.admin-ship-overlay[hidden] { display: none !important; }
.admin-ship-modal {
    width: 100%;
    max-width: 460px;
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 24px 64px rgba(13, 17, 55, 0.22);
    font-family: 'Poppins', sans-serif;
}
.admin-ship-head {
    padding: 24px 24px 18px;
    text-align: center;
    background: #C48A0A;
    color: #fff;
}
.admin-ship-icon {
    width: 54px;
    height: 54px;
    margin: 0 auto 12px;
    border-radius: 50%;
    background: rgba(255,255,255,0.14);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}
.admin-ship-head h3 {
    margin: 0 0 6px;
    font-size: 1.15rem;
    font-weight: 700;
}
.admin-ship-head p {
    margin: 0;
    font-size: 12px;
    color: rgba(255,255,255,0.88);
    line-height: 1.5;
}
.admin-ship-form { padding: 22px 24px 24px; }
.admin-ship-field { margin-bottom: 14px; }
.admin-ship-field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #0d1137;
    margin-bottom: 6px;
}
.admin-ship-field label span { color: #ef4444; }
.admin-ship-field input {
    width: 100%;
    padding: 11px 13px;
    border: 1.5px solid #e4e8f0;
    border-radius: 10px;
    font-family: inherit;
    font-size: 13px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.admin-ship-field input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
.admin-ship-note {
    margin: 0 0 16px;
    font-size: 11px;
    color: #6b7099;
    line-height: 1.5;
    display: flex;
    align-items: flex-start;
    gap: 6px;
}
.admin-ship-note i { color: #2563eb; margin-top: 2px; }
.admin-ship-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
.admin-ship-btn {
    border: none;
    border-radius: 10px;
    padding: 11px 18px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}
.admin-ship-btn.secondary {
    background: #f0f2f8;
    color: #4b5563;
}
.admin-ship-btn.secondary:hover { background: #e4e8f0; }
.admin-ship-btn.primary {
    background: #2563eb;
    color: #fff;
}
.admin-ship-btn.primary:hover { background: #1d4ed8; }
.admin-ship-btn.primary.is-loading {
    opacity: 0.75;
    pointer-events: none;
}
</style>

<script>
(function initAdminShippingOverlay() {
    var overlay = document.getElementById('adminShipOverlay');
    var form = document.getElementById('adminShipForm');
    var orderIdInput = document.getElementById('adminShipOrderId');
    var courierInput = document.getElementById('adminShipCourier');
    var trackingInput = document.getElementById('adminShipTracking');
    var cancelBtn = document.getElementById('adminShipCancel');
    var submitBtn = document.getElementById('adminShipSubmit');

    if (!overlay || !form) return;

    var pendingSelect = null;
    var previousStatus = '';

    function showAdminToast(message, isError) {
        var toast = document.createElement('div');
        toast.style.cssText = 'position:fixed; bottom:30px; right:30px; z-index:13000; background:#fff; color:'
            + (isError ? '#dc2626' : '#16a34a')
            + '; padding:14px 20px; border-radius:12px; font-size:13px; display:flex; align-items:center; gap:10px; box-shadow:0 8px 30px rgba(0,0,0,0.12); border-left:4px solid '
            + (isError ? '#dc2626' : '#16a34a')
            + '; font-family:Poppins,sans-serif;';
        toast.innerHTML = '<i class="fas fa-' + (isError ? 'exclamation-circle' : 'check-circle') + '"></i> ' + message;
        document.body.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 4000);
    }

    function closeOverlay(revertSelect) {
        overlay.hidden = true;
        document.body.style.overflow = '';
        if (revertSelect && pendingSelect && previousStatus) {
            pendingSelect.value = previousStatus;
        }
        pendingSelect = null;
        previousStatus = '';
        form.reset();
        submitBtn.classList.remove('is-loading');
        submitBtn.disabled = false;
    }

    window.openAdminShippingOverlay = function(selectEl, orderId) {
        pendingSelect = selectEl;
        previousStatus = selectEl.getAttribute('data-prev-status') || '';
        orderIdInput.value = orderId;
        courierInput.value = '';
        trackingInput.value = '';
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        courierInput.focus();
    };

    window.handleAdminOrderStatusChange = function(selectEl, orderId) {
        var newValue = selectEl.value;
        var prevValue = selectEl.getAttribute('data-prev-status') || newValue;

        if (newValue === 'shipped' && prevValue !== 'shipped') {
            openAdminShippingOverlay(selectEl, orderId);
            return;
        }

        if (typeof window.submitAdminOrderStatus === 'function') {
            window.submitAdminOrderStatus(orderId, newValue).then(function(result) {
                if (result && result.success) {
                    selectEl.setAttribute('data-prev-status', newValue);
                    showAdminToast(result.message || 'Status updated successfully!');
                } else {
                    selectEl.value = prevValue;
                    showAdminToast((result && result.message) || 'Could not update status.', true);
                }
            });
        }
    };

    cancelBtn.addEventListener('click', function() {
        closeOverlay(true);
    });

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closeOverlay(true);
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        var orderId = orderIdInput.value;
        var courier = courierInput.value.trim();
        var tracking = trackingInput.value.trim();

        if (!courier || !tracking) {
            showAdminToast('Please enter both courier company and tracking ID.', true);
            return;
        }

        if (typeof window.submitAdminOrderStatus !== 'function') {
            closeOverlay(false);
            return;
        }

        var selectRef = pendingSelect;
        var prevStatus = previousStatus;

        if (selectRef) {
            selectRef.setAttribute('data-prev-status', 'shipped');
        }

        closeOverlay(false);
        showAdminToast('Saving shipment details…');

        window.submitAdminOrderStatus(orderId, 'shipped', {
            courier_company: courier,
            tracking_id: tracking
        }).then(function(result) {
            submitBtn.classList.remove('is-loading');
            submitBtn.disabled = false;

            if (result && result.success) {
                showAdminToast(result.message || 'Order marked as shipped. Customer will be notified shortly.');
            } else {
                if (selectRef && prevStatus) {
                    selectRef.value = prevStatus;
                    selectRef.setAttribute('data-prev-status', prevStatus);
                }
                showAdminToast((result && result.message) || 'Could not update order.', true);
            }
        }).catch(function() {
            submitBtn.classList.remove('is-loading');
            submitBtn.disabled = false;
            if (selectRef && prevStatus) {
                selectRef.value = prevStatus;
                selectRef.setAttribute('data-prev-status', prevStatus);
            }
            showAdminToast('Could not update order. Please try again.', true);
        });
    });
})();
</script>
