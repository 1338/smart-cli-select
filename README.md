# smart-cli-select
Symfony selection helper

Select array/objects while being able to add new options

How to call:
```php
    $smartCliSelect = new SmartCliSelect($inputInterface, $outputInterface, $questionHelper);
    
    $result = $smartSelector->smartSelect(
        "Question",
        ["preselected choices indexes"],
        ["choices"],
        ["structure to create new options"]
    );
```





## examples
Object
```php
    $result = $smartSelector->smartSelect(
        "Select products",
        [$allProductsIndexedBySkus[0]],
        $allProductsIndexedBySkus,
        ['type' => 'object']
    );

    $selectedProductIndices = $result['selected'];
    
    foreach ($result['new'] => $newProduct) {
        // do something to new objects, like for example:
        $this->entityManager->persist($newProduct);
        // 
    }
    
    $allProductsIndexedBySkus += $result['new']; // add new products 

    $selectedProducts = [];
    foreach ($selectedProductIndices as $index) {
        $selectedProducts[$index] = $allProductsIndexedBySkus[$index];
    }
    
```
Object with a subset of options to be filled
```php
    $result = $smartSelector->smartSelect(
        "Select products",
        [$allProductsIndexedBySkus[0]],
        $allProductsIndexedBySkus,
        [
            'type' => 'object',
            'options' => [
                'name', 'sku'
            ]
        ]
    );

    $selectedProductIndices = $result['selected'];
    
    foreach ($result['new'] => $newProduct) {
        // do something to new objects, like for example:
        $this->entityManager->persist($newProduct);
        // 
    }
    
    $allProductsIndexedBySkus += $result['new']; // add new products 

    $selectedProducts = [];
    foreach ($selectedProductIndices as $index) {
        $selectedProducts[$index] = $allProductsIndexedBySkus[$index];
    }
```
Array
```php
            $hostResults = $smartSelector->smartSelect(
            'Select hosts',
            array_keys($this->instances),
            $this->input->getOption('host'),
            [
                'options' => ['host', 'username', 'pass', 'port']
            ]
        );
```