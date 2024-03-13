# ServerlessPHPEnvMagic

ServerlessPHPEnvMagic is an innovative open-source library designed to seamlessly integrate and manage serverless PHP environments across various cloud providers such as AWS Lambda, Google Cloud Functions, and Digital Ocean Functions. This library provides a middleware layer that standardizes access to environment variables, session and request data, and file system exploration, facilitating the development of PHP applications in serverless architectures. By abstracting the complexities associated with different serverless environments, ServerlessPHPEnvMagic enables developers to focus on building scalable and efficient PHP applications.

## Founding Author
Tristan McGowan (tristan@ipspy.net)

### Portfolio
[LinkedIn](https://www.linkedin.com/in/tristan-mcgowan-bestdev/ "LinkedIn")

[IP Spy](https://ipspy.net "ipspy.net")

### Recent Projects

[Midwest Public Safety Consulting Group (Branding & Web Tech)](https://mpscg.com "Midwest Public Safety Consulting Group")

[Waypoint Logistics (Custom Proprietary Dispatching/Routing/Tracking Software)](https://waypointlogistics.com "WayPoint Logistics")

## Features

- Unified access to environment variables across different serverless platforms.
- Simplified session and request data management in a serverless context.
- Comprehensive file system exploration tailored for serverless PHP environments.
- Optional inclusion of base64 encoded file contents for in-depth environment analysis.
- Dynamic detection of server resources to tailor environment data retrieval.

## Installation

### Prerequisites

- PHP 7.4 or newer.
- Access to serverless environments such as AWS Lambda, Google Cloud Functions, or Digital Ocean Functions.

### Setup Guide

1. **Clone the Repository**: Clone this repository to your local machine or directly in your serverless environment.

   ```
   git clone https://github.com/your-repo/ServerlessPHPEnvMagic.git
   ```

2. **Deployment**: Deploy the `ServerlessPHPEnvMagic.php` file to your serverless environment, ensuring it's included or autoloaded in your PHP application.

3. **Usage**: Instantiate the `ServerlessPHPEnvMagic` class at the beginning of your application's entry point:

   ```php
   require_once 'ServerlessPHPEnvMagic.php';
   $serverlessEnv = new ServerlessPHPEnvMagic();
   ```

4. **Configuration**: (Optional) Configure the library according to your specific needs, such as enabling file content retrieval based on available memory.

## Usage

After setup, you can use the `ServerlessPHPEnvMagic` class to access environment variables, session data, and file system details. Refer to the class documentation for detailed usage instructions.

Example - Retrieving Environment Data:

```php
$environmentData = $serverlessEnv->getEnvironment();
print_r($environmentData);
```

## Contributing

Contributions to ServerlessPHPEnvMagic are highly encouraged and appreciated. Please fork the repository, create a feature branch, and submit a pull request with your contributions. For more detailed contribution guidelines, please refer to the `CONTRIBUTING.md` file.

## License

ServerlessPHPEnvMagic is released under the MIT License. See the `LICENSE.md` file for more information.

## Acknowledgments

- A heartfelt thank you to Tristan McGowan for initiating and contributing to this project.
- Special appreciation for the open-source community and cloud service providers for their continued support and innovation in the serverless computing space.

**Project Inception Date: Wed, March 13th, 4:41 AM CDT**
