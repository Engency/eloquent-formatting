# Eloquent Custom Formatting

## Requirements

- PHP 7.1+
- Eloquent 7.0+
- Carbon

## Installation

You may use composer to install the eloquent-formatting plugin into your Laravel project;

```shell script
composer require engency/eloquent-formatting
```

Use the CustomDataFormats trait and the ExportsCustomDataFormats interface on your model classes;
 
```php
namespace App\Models;

use Engency\DataStructures\CustomDataFormats;
use Engency\DataStructures\ExportsCustomDataFormats;
use Illuminate\Database\Eloquent\Model;

Class User extends Model implements ExportsCustomDataFormats
{
   use CustomDataFormats;
    
    protected $exports = [
        'default' => [
            'name',
            'age',
            ['posts', ['format' => 'extended']],
            ['created_at', ['dateFormat' => 'yy-m-d']]
        ],
        'limited' => [
            'name'
        ]
    ];

    public function posts() {
        return $this->hasMany(Post::class);    
    }

}

Class Post extends Model implements ExportsCustomDataFormats
{
   use CustomDataFormats;
    
    protected $exports = [
        'default' => [
            'name',
        ],
        'extended' => [
            'name',
            'created_at'
        ]
    ];

}
```

Using the ```$exports``` field, you can specify which attributes should be exported in which situation. Have a look at the User class. The instance exports its name, age, posts and created_at date by default. 

### Basic usage

```php
$user = User::find(1);
$array = $user->toArray(); // exports name, age, posts and created_at
$limited_array = $user->toArray('limited'); // exports only the name-attribute
```  

### Exporting relations or collections

The user class above shows a one-to-many relation between the user and post class. Since the Post class also implements the ExportsCustomDataFormats interface, we can define the desired format. 

```php
['posts', ['format' => 'extended']]
```

### Exporting date attributes

You can choose in which format a Carbon date attribute should be exported. For more information on date formatting, have a look at the Carbon documentation: https://carbon.nesbot.com/docs/#api-formatting

```php
['created_at', ['dateFormat' => 'yy-m-d']]
```

## Contributors

- Frank Kuipers ([GitHub](https://github.com/frankkuipers))

## License

This plugin is licenced under the [MIT license](https://opensource.org/licenses/MIT).
