<?php
require_once '../register/database.php';

// Ensure $connection is available
if (!isset($connection) || !$connection instanceof mysqli) {
    die("Database connection is not properly initialized.");
}
session_start();

// Debug information
echo "Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set') . "<br>";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../register/signIn.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Debug cart contents
$debug_query = "SELECT * FROM cart WHERE user_id = $user_id";
$debug_result = mysqli_query($connection, $debug_query);
echo "Number of items in cart: " . mysqli_num_rows($debug_result) . "<br>";
if ($debug_result) {
    while ($row = mysqli_fetch_assoc($debug_result)) {
        echo "Cart item - user_id: {$row['user_id']}, product_id: {$row['product_id']}, quantity: {$row['quantity']}<br>";
    }
}

$message = null;

// Gérer les actions du panier
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['product_id']) && isset($_POST['action'])) {
        $product_id = intval($_POST['product_id']);
        $action = $_POST['action'];

        switch ($action) {
            case 'add':
                // Vérifier si le produit existe déjà dans le panier
                $check_query = "SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id";
                $check_result = mysqli_query($connection, $check_query);

                if (!$check_result) {
                    $message = "Erreur lors de la vérification du panier: " . mysqli_error($connection);
                } elseif (mysqli_num_rows($check_result) > 0) {
                    // Produit existe déjà, incrémenter la quantité
                    $update_query = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = $user_id AND product_id = $product_id";
                    if (!mysqli_query($connection, $update_query)) {
                        $message = "Erreur lors de la mise à jour: " . mysqli_error($connection);
                    }
                } else {
                    // Nouveau produit, l'ajouter au panier
                    $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, 1)";
                    if (!mysqli_query($connection, $insert_query)) {
                        $message = "Erreur lors de l'ajout: " . mysqli_error($connection);
                    }
                }
                break;

            case 'update':
                if (isset($_POST['quantity'])) {
                    $quantity = intval($_POST['quantity']);
                    if ($quantity > 0 && $quantity <= 10) {
                        $update_query = "UPDATE cart SET quantity = $quantity WHERE user_id = $user_id AND product_id = $product_id";
                        if (!mysqli_query($connection, $update_query)) {
                            $message = "Erreur lors de la mise à jour: " . mysqli_error($connection);
                        }
                    }
                }
                break;

            case 'remove':
                $delete_query = "DELETE FROM cart WHERE user_id = $user_id AND product_id = $product_id";
                if (!mysqli_query($connection, $delete_query)) {
                    $message = "Erreur lors de la suppression: " . mysqli_error($connection);
                }
                break;
        }
        
        // Rafraîchir la page après une action
        header("Location: cart.php");
        exit;
    }
}

// Récupérer les produits du panier avec leurs détails
$query = "SELECT p.id_product, p.name, p.prix, p.image_principale, p.type, c.quantity 
          FROM cart c
          INNER JOIN products p ON c.product_id = p.id_product
          WHERE c.user_id = $user_id";

$result = mysqli_query($connection, $query);

// Vérifier les erreurs SQL
if (!$result) {
    die("<div class='error'>Erreur SQL: " . htmlspecialchars(mysqli_error($connection)) . 
        "<br>Requête: " . htmlspecialchars($query) . "</div>");
}

// Vérifier si le panier est vide
$cart_empty = (mysqli_num_rows($result) === 0);
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infinity-Tech - Cart</title>
    <link rel="stylesheet" href="cart.css">
</head>

<body>
    <?php include '../reusables/navbar.php'; ?>

    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="cart-container">
        <div class="cart-content">
            <?php if (!$cart_empty): ?>
                <div class="cart-items">
                    <?php while ($item = mysqli_fetch_assoc($result)): 
                        $subtotal = $item['prix'] * $item['quantity'];
                        $total += $subtotal;
                    ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <?php if (!empty($item['image_principale'])): ?>
                                    <img src="../assets/<?php echo htmlspecialchars($item['image_principale']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <img src="../assets/default.jpg" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="item-details">
                                <div class="item-title"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-price"><?php echo htmlspecialchars($item['prix']) . "€"; ?></div>
                                <div class="quantity-selector">
                                    <form method="post" action="cart.php">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id_product']; ?>">
                                        <input type="hidden" name="action" value="update">
                                        <select name="quantity" onchange="this.form.submit()">
                                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo ($i == $item['quantity']) ? 'selected' : ''; ?>>
                                                    <?php echo $i; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </form>
                                    <form method="post" action="cart.php">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id_product']; ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <button type="submit" class="remove-btn">Remove</button>
                                    </form>
                                </div>
                                <div class="item-subtotal">Subtotal: <?php echo $subtotal; ?>€</div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="cart-summary">
                    <div class="summary-box">
                        <div class="summary-title">Summary</div>
                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span><?php echo $total . "€"; ?></span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added any products to your cart yet.</p>
                    <a href="../shop/shop.php" class="continue-shopping">Continue Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../reusables/footer.php'; ?>
</body>
</html>