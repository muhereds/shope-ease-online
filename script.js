
const searchInput = document.getElementById("productSearch");
const productCards = document.querySelectorAll(".product-card");
const noResults = document.getElementById("noResults");
const cartCount = document.getElementById("cartCount");
const cartItems = document.getElementById("cartItems");
const cartTotal = document.getElementById("cartTotal");

let cart = [];

try {
    const savedCart = JSON.parse(
        localStorage.getItem("shopease-cart") || "[]"
    );

    if (Array.isArray(savedCart)) {
        cart = savedCart.filter(item =>
            typeof item.name === "string" &&
            Number.isFinite(item.price) &&
            Number.isInteger(item.quantity) &&
            item.price >= 0 &&
            item.quantity > 0
        );
    }
} catch {
    cart = [];
}

function formatPrice(amount) {
    return "ETB " + amount.toLocaleString("en-US");
}

function saveCart() {
    localStorage.setItem("shopease-cart", JSON.stringify(cart));
}

function renderCart() {
    const totalQuantity = cart.reduce(
        (sum, item) => sum + item.quantity, 0
    );

    const totalPrice = cart.reduce(
        (sum, item) => sum + item.price * item.quantity, 0
    );

    cartCount.textContent = totalQuantity;
    cartTotal.textContent = formatPrice(totalPrice);

    if (cart.length === 0) {
        cartItems.innerHTML =
            '<p class="empty-cart">Your cart is empty. Find something you love!</p>';
        return;
    }

    cartItems.replaceChildren();

    cart.forEach(item => {
        const row = document.createElement("div");
        row.className = "cart-row";

        const details = document.createElement("div");
        details.className = "cart-details";

        const name = document.createElement("strong");
        name.textContent = item.name;

        const quantityText = document.createElement("p");
        quantityText.textContent =
            `${item.quantity} × ${formatPrice(item.price)}`;

        details.append(name, quantityText);

        const subtotal = document.createElement("strong");
        subtotal.textContent =
            formatPrice(item.price * item.quantity);

        const controls = document.createElement("div");
        controls.className = "quantity-control";

        const minusButton = document.createElement("button");
        minusButton.type = "button";
        minusButton.textContent = "−";
        minusButton.className = "quantity-button";
        minusButton.setAttribute(
            "aria-label", `Decrease ${item.name} quantity`
        );

        minusButton.addEventListener("click", () => {
            const currentItem = cart.find(
                product => product.name === item.name
            );

            if (!currentItem) return;

            if (currentItem.quantity > 1) {
                currentItem.quantity -= 1;
            } else {
                cart = cart.filter(
                    product => product.name !== item.name
                );
            }

            saveCart();
            renderCart();
        });

        const quantityNumber = document.createElement("span");
        quantityNumber.className = "quantity-number";
        quantityNumber.textContent = item.quantity;

        const plusButton = document.createElement("button");
        plusButton.type = "button";
        plusButton.textContent = "+";
        plusButton.className = "quantity-button";
        plusButton.setAttribute(
            "aria-label", `Increase ${item.name} quantity`
        );

        plusButton.addEventListener("click", () => {
            const currentItem = cart.find(
                product => product.name === item.name
            );

            if (!currentItem) return;

            currentItem.quantity += 1;
            saveCart();
            renderCart();
        });

        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.textContent = "Remove";
        removeButton.className = "remove-button";

        removeButton.addEventListener("click", () => {
            cart = cart.filter(
                product => product.name !== item.name
            );

            saveCart();
            renderCart();
        });

        controls.append(
            minusButton,
            quantityNumber,
            plusButton,
            removeButton
        );

        row.append(details, subtotal, controls);
        cartItems.append(row);
    });
}


    cartItems.replaceChildren();

    cart.forEach(item => {
        const row = document.createElement("div");
        row.className = "cart-row";

        const details = document.createElement("div");
        const name = document.createElement("strong");
        const quantity = document.createElement("p");

        name.textContent = item.name;
        quantity.textContent =
            `Quantity: ${item.quantity} × ${formatPrice(item.price)}`;

        details.append(name, quantity);

        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.textContent = "Remove";
        removeButton.addEventListener("click", () => {
            cart = cart.filter(product => product.name !== item.name);
            saveCart();
            renderCart();
        });

        const subtotal = document.createElement("strong");
        subtotal.textContent = formatPrice(item.price * item.quantity);

        row.append(details, subtotal, removeButton);
        cartItems.append(row);
    });
}

document.querySelectorAll(".add-button").forEach(button => {
    button.addEventListener("click", () => {
        const name = button.dataset.product;
        const price = Number(button.dataset.price);

        if (!name || !Number.isFinite(price) || price < 0) {
            alert("Sorry, this product cannot be added right now.");
            return;
        }

        const existing = cart.find(item => item.name === name);

        if (existing) {
            existing.quantity += 1;
        } else {
            cart.push({ name, price, quantity: 1 });
        }

        saveCart();
        renderCart();

        button.textContent = "Added!";
        setTimeout(() => {
            button.textContent = "Add to cart";
        }, 800);
    });
});

searchInput.addEventListener("input", () => {
    const query = searchInput.value.trim().toLowerCase();
    let visibleCount = 0;

    productCards.forEach(card => {
        const matches = card.dataset.name.toLowerCase().includes(query);
        card.hidden = !matches;

        if (matches) visibleCount++;
    });

    noResults.hidden = visibleCount > 0;
});

document.getElementById("cartButton").addEventListener("click", () => {
    document.getElementById("cart").scrollIntoView({
        behavior: "smooth"
    });
});

document.getElementById("clearCart").addEventListener("click", () => {
    cart = [];
    saveCart();
    renderCart();
});

document.getElementById("currentYear").textContent =
    new Date().getFullYear();

renderCart();


function goToCheckout() {
    const cart = JSON.parse(
        localStorage.getItem("shopease-cart") || "[]"
    );

    if (!Array.isArray(cart) || cart.length === 0) {
        alert("Your cart is empty. Please add a product first.");
        return;
    }

    const products = cart.map(item =>
        `${item.name} x ${item.quantity || 1}`
    ).join(", ");

    const total = cart.reduce((sum, item) =>
        sum + Number(item.price) * (item.quantity || 1), 0
    );

    const params = new URLSearchParams({
        products: products,
        total: total.toFixed(2)
    });

    window.location.href = "checkout.php?" + params.toString();
}


document.addEventListener("DOMContentLoaded", function () {
    const checkoutLink = document.querySelector(".checkout-button");

    if (checkoutLink) {
        checkoutLink.addEventListener("click", function (event) {
            event.preventDefault();
            goToCheckout();
        });
    }
});

