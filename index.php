<?php
require_once __DIR__ . '/db.php';

$stmt = $conn->prepare(
    'SELECT p.id, p.name, p.description, p.price, p.image
     FROM products AS p
     WHERE p.id = (
         SELECT MIN(p2.id)
         FROM products AS p2
         WHERE p2.name = p.name
     )
     ORDER BY p.id DESC'
);


$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopEase | Online Shopping</title>
    <meta name="description" content="Discover great products at ShopEase.">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>

<header class="site-header">
    <a href="index.php" class="logo">Shop<span>Ease</span></a>

    <nav>
        <a href="#home">Home</a>
        <a href="#products">Products</a>
        <a href="#about">About</a>
    </nav>

    <button id="cartButton" class="cart-button" type="button">
        Cart (<span id="cartCount">0</span>)
    </button>
</header>

<main>
    <section class="hero" id="home">
        <div class="hero-content">
            <p class="eyebrow">WELCOME TO SHOPEASE</p>
            <h1>Find your next <span>favorite thing.</span></h1>
            <p>Discover products you'll love, all in one place.</p>
            <a href="#products" class="primary-button">Shop now</a>
        </div>
    </section>

    <section class="products-section" id="products">
        <div class="section-heading">
            <div>
                <p class="eyebrow">OUR COLLECTION</p>
                <h2>Featured products</h2>
            </div>

            <input
                type="search"
                id="productSearch"
                placeholder="Search products..."
                aria-label="Search products"
            >
        </div>

        <div class="product-grid" id="productGrid">

    <?php foreach ($products as $product): ?>
        <article class="product-card"
                 data-name="<?= e($product['name']) ?>">

            <?php if (!empty($product['image'])): ?>
                <img
                    src="images/<?= e(basename($product['image'])) ?>"
                    alt="<?= e($product['name']) ?>"
                    class="product-image"
                >
            <?php else: ?>
                <div class="product-image">No image available</div>
            <?php endif; ?>

            <div class="product-info">
                <p class="product-category">ShopEase Collection</p>
                <h3><?= e($product['name']) ?></h3>

                <p><?= e($product['description'] ?? '') ?></p>

                <div class="product-bottom">
                    <span class="price">
                        ETB <?= number_format((float) $product['price'], 2) ?>
                    </span>

                    <button
                        class="add-button"
                        type="button"
                        data-product="<?= e($product['name']) ?>"
                        data-price="<?= (float) $product['price'] ?>"
                    >
                        Add to cart
                    </button>
                </div>
            </div>
        </article>
    <?php endforeach; ?>

</div>

        <p id="noResults" class="no-results" hidden>
            No products found. Try another search.
        </p>
    </section>

    <section class="cart-section" id="cart">
        <div class="section-heading">
            <div>
                <p class="eyebrow">YOUR SELECTION</p>
                <h2>Shopping cart</h2>
            </div>
        </div>

        <div id="cartItems">
            <p class="empty-cart">Your cart is empty. Find something you love!</p>
        </div>

        <div class="cart-summary">
            <strong>Total: <span id="cartTotal">ETB 0</span></strong>
            <button id="clearCart" class="secondary-button" type="button">
                Clear cart
            </button>
        </div>
      <a href="checkout.php" class="primary-button checkout-button">
    Proceed to Checkout
</a>
        <p class="cart-note">
            This is a demo cart. Checkout and order saving will be added later.
        </p>
    </section>

    <section class="about-section" id="about">
        <h2>Shopping made simple.</h2>
        <p>
            ShopEase brings your favorite products together in one
            convenient place. Browse, discover, and shop with ease.
        </p>
    </section>
</main>

<footer class="site-footer">
    <p>&copy; <span id="currentYear"></span> ShopEase. All rights reserved.</p>
</footer>

<div class="contact-buttons">
    <a href="https://wa.me/251983956587"
       target="_blank"
       rel="noopener noreferrer"
       class="whatsapp-button">
        Contact me on WhatsApp
    </a>

    <a href="https://t.me/myshopowner"
       target="_blank"
       rel="noopener noreferrer"
       class="telegram-button">
        Contact me on Telegram
    </a>
</div>
</body>
</html>
