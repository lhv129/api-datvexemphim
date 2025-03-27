## Installation

1.  **Clone project to github:**
    ```
    git clone https://github.com/lhv129/api-datvexemphim.git
    ```
2.  **Install Composer (if needed):**
    ```bash
    composer install
    composer update
    ```
3.  **Environment configuration on the server:**
    * Create a `.env` file with appropriate environment variables.
4.  **Run migration:**
    ```bash
    php artisan migrate
    ```
5.  **Install JWT:**
    ```bash
    composer require tymon/jwt-auth
    ```
6.  **Install Socialite:**
    ```bash
    composer require laravel/socialite
    ```
7.  **Start the application:**
    ```bash
    php artisan serve
    ```