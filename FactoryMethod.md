# Factory Method

## 1. `app/Contracts/Transport.php`

```php
<?php

namespace App\Contracts;

interface Transport
{
    public function deliver(): void;
}
```

## 2. `app/Transports/Ship.php`

```php
<?php

namespace App\Transports;

use App\Contracts\Transport;

class Ship implements Transport
{
    public function deliver(): void
    {
        echo "Delivering cargo by sea using a ship.";
    }
}
```

## 3. `app/Transports/Truck.php`

```php
<?php

namespace App\Transports;

use App\Contracts\Transport;

class Truck implements Transport
{
    public function deliver(): void
    {
        echo "Delivering cargo by road using a truck.";
    }
}
```

## 4. `app/Logistics/Logistics.php`

```php
<?php

namespace App\Logistics;

use App\Contracts\Transport;

abstract class Logistics
{
    abstract public function createTransport(): Transport;

    public function planDelivery(): void
    {
        $transport = $this->createTransport();

        $transport->deliver();
    }
}
```

## 5. `app/Logistics/SeaLogistics.php`

```php
<?php

namespace App\Logistics;

use App\Contracts\Transport;
use App\Transports\Ship;

class SeaLogistics extends Logistics
{
    public function createTransport(): Transport
    {
        return new Ship();
    }
}
```

## 6. `app/Logistics/RoadLogistics.php`

```php
<?php

namespace App\Logistics;

use App\Contracts\Transport;
use App\Transports\Truck;

class RoadLogistics extends Logistics
{
    public function createTransport(): Transport
    {
        return new Truck();
    }
}
```

## 7. `routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Logistics\SeaLogistics;
use App\Logistics\RoadLogistics;

Route::get('/shipping/sea', function () {
    $logistics = new SeaLogistics();

    $logistics->planDelivery();
});

Route::get('/shipping/road', function () {
    $logistics = new RoadLogistics();

    $logistics->planDelivery();
});
```

## النتائج

عند زيارة `/shipping/sea`:

```text
Delivering cargo by sea using a ship.
```

عند زيارة `/shipping/road`:

```text
Delivering cargo by road using a truck.
```
