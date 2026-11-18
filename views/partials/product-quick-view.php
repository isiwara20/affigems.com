<?php
/**
 * Quick View modal — include once per page (e.g. after product grid).
 */
if (!empty($GLOBALS['ezymart_quick_view_rendered'])) {
    return;
}
$GLOBALS['ezymart_quick_view_rendered'] = true;
$qvRedirect = htmlspecialchars($_SERVER['REQUEST_URI'] ?? BASE_URL . 'shop.php', ENT_QUOTES, 'UTF-8');
?>
<div class="ez-qv" id="ezQuickView" aria-hidden="true">
    <div class="ez-qv__backdrop" data-qv-close></div>
    <div class="ez-qv__panel" role="dialog" aria-modal="true" aria-labelledby="ezQvTitle">
        <div class="ez-qv__header">
            <span class="ez-qv__eyebrow"><i class="fas fa-bolt"></i> Quick View</span>
            <button type="button" class="ez-qv__close" data-qv-close aria-label="Close quick view">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="ez-qv__body">
            <div class="ez-qv__gallery">
                <div class="ez-qv__image-wrap">
                    <img src="" alt="" id="ezQvImage" class="ez-qv__image">
                </div>
                <div class="ez-qv__badges" id="ezQvBadges"></div>
            </div>

            <div class="ez-qv__details">
                <span class="ez-qv__category" id="ezQvCategory" hidden></span>
                <h2 class="ez-qv__title" id="ezQvTitle"></h2>

                <div class="ez-qv__rating" id="ezQvRating"></div>

                <div class="ez-qv__price-row" id="ezQvPricing"></div>
                <p class="ez-qv__save" id="ezQvSave" hidden></p>

                <div class="ez-qv__meta" id="ezQvMeta"></div>

                <div class="ez-qv__actions" id="ezQvActions"></div>

                <a href="#" class="ez-qv__details-link" id="ezQvDetailsLink">
                    View full product details <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    if (window.ezymartOpenQuickView) return;

    var cartUrl = <?php echo json_encode(BASE_URL . 'controllers/CartController.php'); ?>;
    var checkoutUrl = <?php echo json_encode(BASE_URL . 'checkout.php'); ?>;
    var redirectUrl = <?php echo json_encode($qvRedirect); ?>;

    function qvEsc(text) {
        var d = document.createElement('div');
        d.textContent = text == null ? '' : String(text);
        return d.innerHTML;
    }

    function qvStars(rating) {
        var avg = Math.max(0, Math.min(5, parseFloat(rating) || 0));
        var html = '<span class="ez-qv__stars" aria-hidden="true">';
        for (var i = 1; i <= 5; i++) {
            if (i <= Math.floor(avg)) {
                html += '<i class="fas fa-star"></i>';
            } else if (i - avg < 1 && i - avg > 0) {
                html += '<i class="fas fa-star-half-alt"></i>';
            } else {
                html += '<i class="far fa-star"></i>';
            }
        }
        html += '</span>';
        return html;
    }

    window.ezymartOpenQuickView = function(card) {
        if (!card) return;
        var modal = document.getElementById('ezQuickView');
        if (!modal) return;

        var name = card.dataset.name || '';
        var url = card.dataset.url || '#';
        var image = card.dataset.image || '';
        var price = card.dataset.price || '';
        var original = card.dataset.originalPrice || '';
        var discount = parseInt(card.dataset.discount || '0', 10);
        var save = card.dataset.save || '';
        var rating = card.dataset.rating || '0';
        var reviews = parseInt(card.dataset.reviews || '0', 10);
        var sold = parseInt(card.dataset.sold || '0', 10);
        var stock = parseInt(card.dataset.stock || '0', 10);
        var freeShipping = card.dataset.freeShipping === '1';
        var productId = card.dataset.productId || '';
        var hasVariations = card.dataset.hasVariations === '1';
        var category = card.dataset.category || '';

        var img = document.getElementById('ezQvImage');
        img.src = image;
        img.alt = name;

        var catEl = document.getElementById('ezQvCategory');
        if (category) {
            catEl.textContent = category;
            catEl.hidden = false;
        } else {
            catEl.hidden = true;
        }

        document.getElementById('ezQvTitle').textContent = name;

        document.getElementById('ezQvRating').innerHTML =
            qvStars(rating) +
            '<span class="ez-qv__rating-text"><strong>' + parseFloat(rating || 0).toFixed(1) + '</strong> (' +
            reviews.toLocaleString() + ' review' + (reviews === 1 ? '' : 's') + ')</span>';

        var pricing = '<span class="ez-qv__price">' + qvEsc(price) + '</span>';
        if (original) {
            pricing += '<span class="ez-qv__price-old">' + qvEsc(original) + '</span>';
            if (discount >= 5) {
                pricing += '<span class="ez-qv__discount">' + discount + '% OFF</span>';
            }
        }
        document.getElementById('ezQvPricing').innerHTML = pricing;

        var saveEl = document.getElementById('ezQvSave');
        if (save) {
            saveEl.innerHTML = '<i class="fas fa-tag"></i> You save <strong>' + qvEsc(save) + '</strong>';
            saveEl.hidden = false;
        } else {
            saveEl.hidden = true;
        }

        var meta = '';
        if (sold > 0) {
            meta += '<span class="ez-qv__meta-item ez-qv__meta-item--sold"><i class="fas fa-fire"></i> ' + sold.toLocaleString() + ' sold</span>';
        }
        if (stock > 0 && stock <= 50) {
            meta += '<span class="ez-qv__meta-item ez-qv__meta-item--stock"><i class="fas fa-box"></i> Only ' + stock.toLocaleString() + ' left</span>';
        } else if (stock <= 0) {
            meta += '<span class="ez-qv__meta-item ez-qv__meta-item--out"><i class="fas fa-times-circle"></i> Out of stock</span>';
        }
        document.getElementById('ezQvMeta').innerHTML = meta;

        var badges = '';
        if (freeShipping) {
            badges += '<span class="ez-qv__pill ez-qv__pill--ship"><i class="fas fa-shipping-fast"></i> Free Shipping</span>';
        }
        if (discount >= 5) {
            badges += '<span class="ez-qv__pill ez-qv__pill--deal"><i class="fas fa-percent"></i> ' + discount + '% OFF</span>';
        }
        document.getElementById('ezQvBadges').innerHTML = badges;

        var actions = '';
        if (stock > 0 && !hasVariations) {
            actions +=
                '<form action="' + cartUrl + '" method="POST" class="ez-qv__form">' +
                '<input type="hidden" name="action" value="add">' +
                '<input type="hidden" name="product_id" value="' + qvEsc(productId) + '">' +
                '<input type="hidden" name="quantity" value="1">' +
                '<input type="hidden" name="redirect_url" value="' + qvEsc(redirectUrl) + '">' +
                '<button type="submit" class="ez-qv__btn ez-qv__btn--cart"><i class="fas fa-shopping-cart"></i> Add to Cart</button></form>' +
                '<form action="' + cartUrl + '" method="POST" class="ez-qv__form">' +
                '<input type="hidden" name="action" value="add">' +
                '<input type="hidden" name="product_id" value="' + qvEsc(productId) + '">' +
                '<input type="hidden" name="quantity" value="1">' +
                '<input type="hidden" name="redirect_url" value="' + qvEsc(checkoutUrl) + '">' +
                '<button type="submit" class="ez-qv__btn ez-qv__btn--buy"><i class="fas fa-bolt"></i> Buy Now</button></form>';
        } else if (stock > 0) {
            actions += '<a href="' + qvEsc(url) + '" class="ez-qv__btn ez-qv__btn--cart ez-qv__btn--wide"><i class="fas fa-sliders-h"></i> Select Options</a>';
        } else {
            actions += '<a href="' + qvEsc(url) + '" class="ez-qv__btn ez-qv__btn--cart ez-qv__btn--wide"><i class="fas fa-eye"></i> View Product</a>';
        }
        document.getElementById('ezQvActions').innerHTML = actions;

        var detailsLink = document.getElementById('ezQvDetailsLink');
        detailsLink.href = url;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ez-qv-open');
    };

    window.ezymartCloseQuickView = function() {
        var modal = document.getElementById('ezQuickView');
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ez-qv-open');
    };

    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-qv-close]')) {
            ezymartCloseQuickView();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') ezymartCloseQuickView();
    });
})();
</script>
