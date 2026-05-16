# omnivideo/omnivideo-sdk (PHP)

PHP client for [Omni Video](https://omnivideo.net/) — generate video and image content with the **Gemini Omni Video** series of models.

[Omni Video](https://omnivideo.net/) hosts the Gemini Omni Video family (`seedance-2` for text/image → video, `gpt-image-2` and `nano-banana-2` for text/image → image) behind one simple REST API.

## Install

```bash
composer require omnivideo/omnivideo-sdk
```

## Get an API key

Sign in at **<https://omnivideo.net/>**, open the account page, then create a `sk-…` token.

```bash
export OMNIVIDEO_API_KEY=sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

## Quick start

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use OmniVideo\Client;

$client = new Client(); // reads OMNIVIDEO_API_KEY

$task = $client->run([
    'model_id' => 'seedance-2',
    'prompt' => 'a serene zen garden at sunrise, ultra detailed',
    'aspect_ratio' => '16:9',
]);

echo $task->outputUrl(); // video_url or image_url
```

### Lower level

```php
$task = $client->createTask([
    'model_id' => 'gpt-image-2',
    'prompt' => 'cyberpunk corgi, neon rim light',
]);

while (!$task->isDone()) {
    sleep(3);
    $task = $client->getTask($task->taskId);
}
echo $task->imageUrl;
```

## Models

| `model_id`      | Modality           | Output      |
| --------------- | ------------------ | ----------- |
| `seedance-2`    | text/image → video | `video_url` |
| `gpt-image-2`   | text/image → image | `image_url` |
| `nano-banana-2` | text/image → image | `image_url` |

## Links

- Website & account: <https://omnivideo.net/>
- API docs: <https://omnivideo.net/api-docs>

## License

MIT
