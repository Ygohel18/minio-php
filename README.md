# Minio PHP Uploader

This is a PHP scripts for uploading images and videos to Minio for personal purpose, built using the AWS SDK for PHP.

## Requirements

- PHP 7.2 or higher
- Composer
- AWS SDK for PHP
- vlucas/phpdotenv

## Installation

1. **Install Composer dependencies:**

    ```sh
    composer require aws/aws-sdk-php vlucas/phpdotenv
    ```

2. **Run Composer update:**

    ```sh
    composer update
    ```

## Usage

### Environment Configuration

Create a `.env` file in the root of your project and add the following configuration:

```env
AWS_ENDPOINT=https://your-minio-endpoint
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### Contact

For support, please contact:

- Email: [me@yashgohel.com](mailto:me@yashgohel.com)  
- Instagram: [@ygohel18](https://instagram.com/ygohel18)  
- LinkedIn: [@ygohel18](https://linkedin.com/in/ygohel18)
- X (Twitter): [@ygohel18](https://x.com/ygohel18)


### Credits

This script was created with the help of ChatGPT.