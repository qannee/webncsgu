// assets/js/main.js

// Tự động đóng alert sau 4 giây
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity 0.5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    }, 4000);
});

// Confirm delete
document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', e => {
        if (!confirm(btn.dataset.confirm)) e.preventDefault();
    });
});

// Close modals on backdrop click
document.querySelectorAll('[id^="modal-"]').forEach(modal => {
    modal.addEventListener('click', e => {
        if (e.target === modal) modal.style.display = 'none';
    });
});

// Qty input validation
document.querySelectorAll('.qty-control input[type=number]').forEach(input => {
    input.addEventListener('change', function() {
        const min = parseInt(this.min) || 1;
        const max = parseInt(this.max) || 9999;
        if (this.value < min) this.value = min;
        if (this.value > max) this.value = max;
    });
});

// Auto calculate sell price from cost + profit
const calcSell = () => {
    const cost   = parseFloat(document.getElementById('add_cost')?.value) || 0;
    const profit = parseFloat(document.getElementById('add_profit')?.value) || 0;
    const sell   = Math.round(cost * (1 + profit / 100));
    const preview = document.getElementById('sell_preview');
    if (preview) preview.textContent = sell.toLocaleString('vi-VN') + '₫';
};
document.getElementById('add_cost')?.addEventListener('input', calcSell);
document.getElementById('add_profit')?.addEventListener('input', calcSell);

console.log('🌿 FoodShop loaded!');
