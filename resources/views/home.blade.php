<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <!-- Styles -->
        <style>

        </style>
    </head>
    <body class="">
        <div class="container">
            <div class="pt-3">
                <a href="/"><h4>Cars Page</h4></a>
            </div>
            <div class="row pt-5">
                <div class="col-md-2">
                    <h3>Categories</h5>
                    <form action="{{ route('filterCars') }}" method="get">
                        <select name="category" id="category">
                            <option value="null">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>

                        <select name="brand" id="brand">
                            <option value="">All Brands</option>
                        </select>
                        
                        <button type="submit">Filter</button>
                    </form>
                </div>
                <div class="col-md-9">
                    <div id="car-listings" class="row">
                        @foreach ($cars as $car)
                            <div class="card m-1" style="width: 18rem;">
                                <img src="{{ $car->images[0]['image_path'] }}" class="card-img-top" alt="...">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $car['name'] }}</h5>
                                    <a href="#" class="btn btn-primary">{{ number_format($car['amount']) }} {{ $car->currency_code }}</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="p-5">
                        {{ $cars->appends(['category' => request('category'), 'brand' => request('brand')])->links("pagination::bootstrap-5") }}
                    </div>
                </div>
                <div class="col-md-1">
                    -
                </div>
            </div>
        </div>
    </body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        document.getElementById('category').addEventListener('change', function () {
            let selectedCategoryId = this.value;
            let brandDropdown = document.getElementById('brand');

            // Clear previous brand options
            brandDropdown.innerHTML = '<option value="">Select Brand</option>';
            brandDropdown.disabled = true;

            if (selectedCategoryId) {
                // Fetch brands related to the selected category using an AJAX request
                fetch(`/get-brands/${selectedCategoryId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Populate the brand dropdown with retrieved data
                        data.brands.forEach(brand => {
                            const option = document.createElement('option');
                            option.value = brand.id;
                            option.textContent = brand.name;
                            brandDropdown.appendChild(option);
                        });
                        brandDropdown.disabled = false;
                    })
                    .catch(error => console.error(error));
            }
        });
    </script>
</html>
