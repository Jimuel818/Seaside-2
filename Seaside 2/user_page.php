<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seaside Floating Restaurant</title>
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Courier New', Courier, monospace;
}

body {
    margin: 0;
    padding: 0;
    text-align: center;
    background-color: #f5ebdc;
    font-family: 'Courier New', Courier, monospace;
    background-image: url('Background.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    color: #070354;
    background-color: rgba(0, 0, 0, 0.4);
    padding: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 1000;
}

.logo img {
    width: 50px;
    height: 50px;
    margin-left: 20px;
}

nav ul {
    list-style: none;
    display: flex;
}

nav ul li {
    margin: 0 15px;
}

nav ul li a {
    color: white;
    text-decoration: none;
    font-size: 18px;
    font-weight: bold;
    transition: all ease 0.5s;
}

nav ul li a:hover {
    background-color: #000b41;
    padding: 5px 10px;
    border-radius: 5px;
}

main {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding-top: 120px;
    height: auto;
    min-height: 80vh;
}

.center-logo {
    width: 350px;
    height: auto;
    margin-bottom: 30px;
}

h1 {
    font-size: 60px;
    font-family: 'Serif';
    text-transform: uppercase;
    font-weight: bold;
}

h2 {
    font-size: 20px;
    color: #e6e6e6;
    font-family: Copperplate, Papyrus, fantasy;
    margin-top: 20px;
}

h3 {
    font-size: 40px;
    color: #000b41;
    font-family: Copperplate, Papyrus, fantasy;
    padding: 20px 0 30px;
}

p {
    color: white;
    font-family: 'Courier New', Courier, monospace;
}

button {
    background-color: #2c3e50;
    color: white;
    border: 2px solid #ffffff;
    padding: 10px 20px;
    font-size: 18px;
    font-weight: bold;
    font-family: Copperplate, Papyrus, fantasy;
    cursor: pointer;
    border-radius: 5px;
    transition: transform 0.3s ease, background-color 0.3s ease;
}

button:hover {
    transform: translateY(-10px);
    background-color: #980000;
}

section {
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
#menuu {
    background: linear-gradient(#2e6193, #2e938e);
    padding-top: 20px;
}

#APPETIZERS {
    background: linear-gradient(#2e6193, #2e938e);
    padding: 40px 20px;
}

#SPECIALS {
    background: linear-gradient(#2e938e, #2e6193);
    padding: 40px 20px;
}
#CRABS {
    background: linear-gradient(#2e6193, #2e938e);
    padding: 40px 20px;
}

#SIZZLINGGRILLED {
    background: linear-gradient(#2e938e, #2e6193);
    padding: 40px 20px;
}
#RICE {
    background: linear-gradient(#2e6193, #2e938e);
    padding: 40px 20px;
}

#BEVERAGES{
    background: linear-gradient(#2e938e, #2e6193);
    padding: 40px 20px;
}
#SOUP {
    background: linear-gradient(#2e6193, #2e938e);
    padding: 40px 20px;
}

#PORK {
    background: linear-gradient(#2e938e, #2e6193);
   
    padding: 40px 20px;
}
#CHICKEN {
    background: linear-gradient(#2e6193, #2e938e);
    padding: 40px 20px;
}

#DESSERTS {
    background: linear-gradient(#2e938e, #2e6193);
    padding: 40px 20px;
}

.menu-title {
    padding-top: 60px;
    color: #f5ebdc;
}

.menu-categories {
    padding-top: 20px;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}

.menu-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.menu-item,
.menu-column p {
    display: flex;
    justify-content: space-between;
    font-size: 18px;
    padding: 5px 10px;
    color: white;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.menu-column {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.location {
    background: linear-gradient( #2e938e, #2e6193);
    height: auto;
    padding: 30px 20px;
    max-width: 800px;
    margin: 40px auto;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.gallery-images {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px;
}

.gallery-images .image img {
    width: 350px;
    height: 350px;
    border-radius: 5px;
    padding-top: 50px;
}

footer {
    background-color: #2d5c88;
    color: white;
    padding: 30px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: center;
    justify-content: center;
}

/* Responsive Fixes */
@media (max-width: 768px) {
    nav ul {
        flex-direction: column;
        align-items: center;
    }

    .menu-grid {
        grid-template-columns: 1fr;
    }

    .center-logo {
        width: 80%;
    }
}

    </style>
</head>
<body>  

    <header>
        <div class="logo">
        <a href="user_page.php">
        <img src="logo.png" alt="Restaurant logo">
        </div>  
        <nav>   
            <ul>
                <li><a href="about.php">About</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="reservation.php">Reservation</a></li>
                <li><a href="gallery.php">Gallery</a></li>
                <li><a href="index.php">Login</a></li>
            </ul>
        </nav>
    </header>
    <br>    
    <br>
    <br>
    <main>
        <div class="content">
            <img src="logo.png" alt="Restaurant Logo" class="center-logo">
            <h2>SEA-SIDE RESTAURANT</h2>
            <br>
            <a href="reservation.php">
  <button type="button">RESERVE HERE</button>
</a>

</form>
        </div>
    </main>
   

    <br>
    <h2>SEASIDE FLOATING RESTAURANT</h2>
    <p>Welcome to Seaside Floating Restaurant, a one-of-a-kind dining spot in Bacolod, Philippines.<br> Enjoy breathtaking ocean views and the freshest seafood in town.</p>


    <section id="menuu">
        <h1 class="menu-title">MENU</h1>

        <div class="menu-categories">
<button type="button" onclick="document.getElementById('APPETIZERS').scrollIntoView({ behavior: 'smooth' })">APPETIZERS</button>
<button type="button" onclick="document.getElementById('SPECIALS').scrollIntoView({ behavior: 'smooth' })">SPECIALS</button>
<button type="button" onclick="document.getElementById('SPECIALS').scrollIntoView({ behavior: 'smooth' })">CRABS</button>
<button type="button" onclick="document.getElementById('CRABS').scrollIntoView({ behavior: 'smooth' })">SIZZLING/GRILLED</button>
<button type="button" onclick="document.getElementById('RICE').scrollIntoView({ behavior: 'smooth' })">RICE</button>
<button type="button" onclick="document.getElementById('RICE').scrollIntoView({ behavior: 'smooth' })">BEVERAGES</button>
<button type="button" onclick="document.getElementById('BEVERAGES').scrollIntoView({ behavior: 'smooth' })">SOUP</button>
<button type="button" onclick="document.getElementById('SOUP').scrollIntoView({ behavior: 'smooth' })">PORK</button>
<button type="button" onclick="document.getElementById('PORK').scrollIntoView({ behavior: 'smooth' })">CHICKEN</button>
<button type="button" onclick="document.getElementById('CHICKEN').scrollIntoView({ behavior: 'smooth' })">DESSERTS</button>

        </div>
    </section>
        
    <section id="APPETIZERS">
    <h3 class="section-title">APPETIZERS</h3>

        <div class="menu-items">
            <div class="menu-column">
                <p><span>BUTTERED SHRIMP</span> <span>320</span></p>
                <p><span>CALAMARES</span> <span>320</span></p>
                <p><span>CAMARON REBOSADO</span> <span>320</span></p>
                <p><span>CHEESE STICKS</span> <span>150</span></p>
                <p><span>FRENCH FRIES</span> <span>85</span></p>
            </div>
            <div class="menu-column">
                <p><span>KINILAW</span> <span>320</span></p>
                <p><span>KROPEK</span> <span>95</span></p>
                <p><span>LUMPIA SHANGHAI</span> <span>180</span></p>
                <p><span>ONION RINGS</span> <span>175</span></p>
                <p><span>TALABA</span> <span>95</span></p>
            </div>
        </div>
    </section>

    <section id="SPECIALS">
    <h3 class="section-title">SPECIALS</h3>

        <div class="menu-items">
            <div class="menu-column">
                <p><span>Adobo (Alimusan)</span> <span>320</span></p>
                <p><span>Adobo Locus</span> <span>320</span></p>
                <p><span>Chopsuey</span> <span>320</span></p>
                <p><span>Bam-i</span> <span>150</span></p>
                <p><span>Bihon Guisado</span> <span>85</span></p>
                <p><span>Pancit Canton Guisado</span> <span>85</span></p>
                <p><span>Sotanghon Guisado</span> <span>85</span></p>
            </div>
    </section>

    <section id="CRABS">
    <h3 class="section-title">CRABS</h3>

        <div class="menu-items">
            <div class="menu-column">
                <p><span>Crab w/ Sweet Chili</span> <span>320</span></p>
                <p><span>Salt & peper</span> <span>320</span></p>
            </div>
    </section>

    <section id="SIZZLINGGRILLED">
    <h3 class="section-title">SIZZLING/GRILLED</h3>

        <div class="menu-items">
            <div class="menu-column">
                <p><span>Boneless Bangus</span> <span>320</span></p>
                <p><span>Blue Marlin</span> <span>320</span></p>
                <p><span>Buttered Squid</span> <span>320</span></p>
                <p><span>Stuffed Squid</span> <span>150</span></p>
                <p><span>Gambas (250g)</span> <span>85</span></p>
                <p><span>Mixed Seafoods</span> <span>85</span></p>
                <p><span>Pork Sisig</span> <span>85</span></p>
                <p><span>Tangigue (350g)</span> <span>85</span></p>
                <p><span>Tilapia</span> <span>85</span></p>
            </div>
    </section>

    <section id="RICE">
    <h3 class="section-title">RICE</h3>

        <div class="menu-items">
            <div class="menu-column">
                <p><span>Plain Rice</span> <span>320</span></p>
                <p><span>Garlic Rice</span> <span>320</span></p>
                <p><span>Fried Rice</span> <span>320</span></p>
                <p><span>Fried W Vegetable</span> <span>150</span></p>
                <p><span>Fried W Seafood</span> <span>85</span></p>
            </div>
    </section>

    <section id="BEVERAGES">
    <h3 class="section-title">BEVERAGES</h3>

        <div class="menu-items">
            <div class="menu-column">
                <p><span>Calamansi</span> <span>320</span></p>
                <p><span>Iced Tea Glass</span> <span>320</span></p>
                <p><span>Iced Tea</span> <span>320</span></p>
                <p><span>Cucumber Lemonade Pitcher</span> <span>150</span></p>
                <p><span>Canned Juice</span> <span>85</span></p>
                <p><span>Pepsi/7UP Bottle</span> <span>85</span></p>
                <p><span>Mountaun Dew Bottle</span> <span>85</span></p>
                <p><span>Canned Softdrinks</span> <span>85</span></p>
                <p><span>1.5L Softdrinks</span> <span>85</span></p>
                <p><span>Fresh Fruit Shakes</span> <span>85</span></p>
                <p><span>San Mig Light</span> <span>85</span></p>
                <p><span>Pale Pilsen</span> <span>85</span></p>
                <p><span>Red Horse (Stallion)</span> <span>85</span></p>
            </div>
    </section>

    <section id="SOUP">
    <h3 class="section-title">SOUP</h3>

        <div class="menu-items">
            <div class="menu-column">
                <p><span>Sinigang Fish</span> <span>320</span></p>
                <p><span>Sinigang Pork</span> <span>320</span></p>
                <p><span>Sinigang Shrimp</span> <span>320</span></p>
                <p><span>Shells Soup</span> <span>150</span></p>
                <p><span>Tinola Manok</span> <span>85</span></p>
            </div>
    </section>

    
    <section id="PORK">
    <h3 class="section-title">PORK</h3>

        <div class="menu-items">
            <div class="menu-column">
            <p><span>Crispy Pata (1.2kg)</span> <span>85</span></p>
            <p><span>Pork Adobo</span> <span>85</span></p>
            <p><span>Pork Belly</span> <span>85</span></p>
            <p><span>Pork Chop</span> <span>85</span></p>
            </div>
    </section>

    <section id="CHICKEN">
    <h3 class="section-title">CHICKEN</h3>

        <div class="menu-items">
            <div class="menu-column">
            <p><span>Buttered Chicken</span> <span>85</span></p>
            <p><span>Chicken Adobo</span> <span>85</span></p>
            </div>
    </section>

    <section id="CHICKEN">
    <h3 class="section-title">DESSERTS</h3>

        <div class="menu-items">
            <div class="menu-column">
            <p><span>Leche Flan</span> <span>85</span></p>
            <p><span>Ice Cream</span> <span>85</span></p>
            </div>
    </section>




<section class="location">
    <h3>OUR LOCATION</h3>
    <p>Seaside Floating Restaurant, Barangay Balaring, Silay City, Negros Occidental, Philippines</p>
    <form action="location.php" method="POST">
        <br>
        <br>
        <br>
        
  <button type="submit" name="location">View Location</button>
</form>
</section>

<script src="function.js"></script>

<footer>
    <p>Operating Hours: Monday - Sunday, 8:00 AM - 9:00 PM</p>
    <p>Contact: +63 947 968 8950 | Email: info@seasidefloating.com</p>
</footer>

</body>
</html>