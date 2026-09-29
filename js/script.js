// ========================================
// KHARIDO - SEARCH, FILTER, SORT & PAGINATION
// ========================================

let selectedCategory = "all";

// PRODUCTS PER PAGE
const productsPerPage = 8;

// CURRENT PAGE
let currentPage = 1;


// ========================================
// ADD TO CART
// ========================================

function addToCart(productId, productName) {
    alert(productName + " added to cart!");
}


// ========================================
// SEARCH PRODUCTS
// ========================================

function searchProducts() {
    currentPage = 1;
    applyFilters();
}


// ========================================
// FILTER CATEGORY
// ========================================

function filterCategory(category) {

    selectedCategory = String(category);

    currentPage = 1;

    setActiveCategory(selectedCategory);

    applyFilters();
}


// ========================================
// SHOW ALL PRODUCTS
// ========================================

function showAllProducts() {

    selectedCategory = "all";

    currentPage = 1;

    setActiveCategory("all");

    applyFilters();
}


// ========================================
// ACTIVE CATEGORY HIGHLIGHT
// ========================================

function setActiveCategory(category) {

    const categoryCards =
        document.querySelectorAll(".category-card");

    categoryCards.forEach(function(card) {

        const cardCategory =
            card.getAttribute("data-category");

        card.classList.remove("active");


        // ALL PRODUCTS

        if (
            category === "all" &&
            !cardCategory
        ) {

            card.classList.add("active");

        }


        // NORMAL CATEGORY

        else if (
            cardCategory &&
            String(cardCategory) === String(category)
        ) {

            card.classList.add("active");

        }

    });
}


// ========================================
// APPLY SEARCH + CATEGORY + PRICE FILTER
// ========================================

function applyFilters() {

    const searchInput =
        document.getElementById("searchInput");

    const searchValue =
        searchInput
            ? searchInput.value.toLowerCase().trim()
            : "";


    const priceFilter =
        document.getElementById("priceFilter");

    const selectedPrice =
        priceFilter
            ? priceFilter.value
            : "all";


    const products =
        document.querySelectorAll(".product-card");


    let filteredProducts = [];


    // ========================================
    // FILTER PRODUCTS
    // ========================================

    products.forEach(function(product) {

        const productName =
            (
                product.getAttribute("data-name") || ""
            ).toLowerCase();


        const productCategory =
            String(
                product.getAttribute("data-category") || ""
            );


        const productPrice =
            parseFloat(
                product.getAttribute("data-price") || "0"
            );


        // SEARCH MATCH

        const searchMatch =
            productName.includes(searchValue);


        // CATEGORY MATCH

        const categoryMatch =
            selectedCategory === "all" ||
            productCategory === selectedCategory;


        // PRICE MATCH

        let priceMatch = true;


        if (selectedPrice === "0-500") {

            priceMatch =
                productPrice >= 0 &&
                productPrice <= 500;

        }

        else if (selectedPrice === "500-1000") {

            priceMatch =
                productPrice > 500 &&
                productPrice <= 1000;

        }

        else if (selectedPrice === "1000-2000") {

            priceMatch =
                productPrice > 1000 &&
                productPrice <= 2000;

        }

        else if (selectedPrice === "2000+") {

            priceMatch =
                productPrice > 2000;

        }


        // FINAL MATCH

        if (
            searchMatch &&
            categoryMatch &&
            priceMatch
        ) {

            filteredProducts.push(product);

        }

    });


    // ========================================
    // SHOW / HIDE PRODUCTS
    // ========================================

    products.forEach(function(product) {

        product.style.display = "none";

    });


    // ========================================
    // PAGINATION
    // ========================================

    const totalPages =
        Math.ceil(
            filteredProducts.length / productsPerPage
        );


    if (currentPage > totalPages && totalPages > 0) {

        currentPage = totalPages;

    }


    const startIndex =
        (currentPage - 1) * productsPerPage;


    const endIndex =
        startIndex + productsPerPage;


    const productsToShow =
        filteredProducts.slice(
            startIndex,
            endIndex
        );


    productsToShow.forEach(function(product) {

        product.style.display = "";

    });


    // ========================================
    // NO PRODUCTS MESSAGE
    // ========================================

    let noProductsMessage =
        document.getElementById(
            "noProductsMessage"
        );


    if (filteredProducts.length === 0) {

        if (!noProductsMessage) {

            noProductsMessage =
                document.createElement("div");

            noProductsMessage.id =
                "noProductsMessage";

            noProductsMessage.className =
                "no-products";

            noProductsMessage.innerHTML = `
                <h3>No Products Found</h3>
                <p>
                    We couldn't find any product matching your search or filters.
                </p>
            `;

            const productsGrid =
                document.querySelector(
                    ".products-grid"
                );

            if (productsGrid) {

                productsGrid.appendChild(
                    noProductsMessage
                );

            }

        }

        noProductsMessage.style.display =
            "block";

    }

    else {

        if (noProductsMessage) {

            noProductsMessage.style.display =
                "none";

        }

    }


    // ========================================
    // CREATE PAGINATION
    // ========================================

    createPagination(totalPages);

}


// ========================================
// SORT PRODUCTS
// ========================================

function sortProducts() {

    const sortSelect =
        document.getElementById("sortProducts");


    if (!sortSelect) {
        return;
    }


    const sortValue =
        sortSelect.value;


    const productsGrid =
        document.querySelector(".products-grid");


    if (!productsGrid) {
        return;
    }


    const products =
        Array.from(
            productsGrid.querySelectorAll(
                ".product-card"
            )
        );


    // ========================================
    // PRICE LOW TO HIGH
    // ========================================

    if (sortValue === "low-high") {

        products.sort(function(a, b) {

            const priceA =
                parseFloat(
                    a.getAttribute("data-price") || "0"
                );


            const priceB =
                parseFloat(
                    b.getAttribute("data-price") || "0"
                );


            return priceA - priceB;

        });

    }


    // ========================================
    // PRICE HIGH TO LOW
    // ========================================

    else if (sortValue === "high-low") {

        products.sort(function(a, b) {

            const priceA =
                parseFloat(
                    a.getAttribute("data-price") || "0"
                );


            const priceB =
                parseFloat(
                    b.getAttribute("data-price") || "0"
                );


            return priceB - priceA;

        });

    }


    // ========================================
    // NAME A TO Z
    // ========================================

    else if (sortValue === "name-az") {

        products.sort(function(a, b) {

            const nameA =
                (
                    a.getAttribute("data-name") || ""
                ).toLowerCase();


            const nameB =
                (
                    b.getAttribute("data-name") || ""
                ).toLowerCase();


            return nameA.localeCompare(nameB);

        });

    }


    // ========================================
    // NAME Z TO A
    // ========================================

    else if (sortValue === "name-za") {

        products.sort(function(a, b) {

            const nameA =
                (
                    a.getAttribute("data-name") || ""
                ).toLowerCase();


            const nameB =
                (
                    b.getAttribute("data-name") || ""
                ).toLowerCase();


            return nameB.localeCompare(nameA);

        });

    }


    // ========================================
    // PUT PRODUCTS BACK
    // ========================================

    products.forEach(function(product) {

        productsGrid.appendChild(product);

    });


    // RESET PAGE

    currentPage = 1;


    // APPLY PAGINATION AGAIN

    applyFilters();

}


// ========================================
// CREATE PAGINATION BUTTONS
// ========================================

function createPagination(totalPages) {

    const pagination =
        document.getElementById(
            "pagination"
        );


    if (!pagination) {
        return;
    }


    pagination.innerHTML = "";


    // No pagination needed

    if (totalPages <= 1) {

        return;

    }


    // ========================================
    // PREVIOUS BUTTON
    // ========================================

    const previousButton =
        document.createElement("button");


    previousButton.innerHTML =
        "← Previous";


    previousButton.className =
        "pagination-button";


    previousButton.disabled =
        currentPage === 1;


    previousButton.onclick =
        function() {

            if (currentPage > 1) {

                currentPage--;

                applyFilters();

                scrollToProducts();

            }

        };


    pagination.appendChild(
        previousButton
    );


    // ========================================
    // PAGE NUMBERS
    // ========================================

    for (
        let page = 1;
        page <= totalPages;
        page++
    ) {

        const pageButton =
            document.createElement("button");


        pageButton.innerHTML =
            page;


        pageButton.className =
            "pagination-button";


        if (page === currentPage) {

            pageButton.classList.add(
                "active"
            );

        }


        pageButton.onclick =
            function() {

                currentPage = page;

                applyFilters();

                scrollToProducts();

            };


        pagination.appendChild(
            pageButton
        );

    }


    // ========================================
    // NEXT BUTTON
    // ========================================

    const nextButton =
        document.createElement("button");


    nextButton.innerHTML =
        "Next →";


    nextButton.className =
        "pagination-button";


    nextButton.disabled =
        currentPage === totalPages;


    nextButton.onclick =
        function() {

            if (
                currentPage < totalPages
            ) {

                currentPage++;

                applyFilters();

                scrollToProducts();

            }

        };


    pagination.appendChild(
        nextButton
    );

}


// ========================================
// SCROLL TO PRODUCTS
// ========================================

function scrollToProducts() {

    const productsSection =
        document.getElementById(
            "products"
        );


    if (productsSection) {

        productsSection.scrollIntoView({
            behavior: "smooth"
        });

    }

}


// ========================================
// LIVE SEARCH
// ========================================

document.addEventListener(
    "DOMContentLoaded",
    function() {

        const searchInput =
            document.getElementById(
                "searchInput"
            );


        if (searchInput) {

            searchInput.addEventListener(
                "input",
                function() {

                    currentPage = 1;

                    applyFilters();

                }
            );

        }


        // DEFAULT CATEGORY

        setActiveCategory("all");


        // INITIAL FILTER

        applyFilters();

    }
);