<?php

namespace CrowdSec\CapiClient\Tests;

/**
 * Mocked data for unit test.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class MockedData
{
    const HTTP_200 = 200;
    const HTTP_400 = 400;
    const HTTP_401 = 401;
    const HTTP_403 = 403;
    const HTTP_500 = 500;

    const LOGIN_SUCCESS = <<<EOT
{"code": 200, "token": "this-is-a-token", "expire": "YYYY-MM-ddThh:mm:ssZ"}
EOT;

    const LOGIN_BAD_CREDENTIALS = <<<EOT
{
  "message": "The machine_id or password is incorrect"
}
EOT;

    const REGISTER_ALREADY = <<<EOT
{
  "message": "User already registered."
}
EOT;

    const SUCCESS = <<<EOT
{"message":"OK"}
EOT;

    const BAD_REQUEST = <<<EOT
{
  "message": "Invalid request body",
  "errors": "[Unknown error parsing request body]"
}
EOT;

    const SIGNALS_BAD_REQUEST = <<<EOT
{
  "message": "Invalid request body",
  "errors": "[object has missing required properties ([\"scenario_hash\"])]"
}
EOT;

    const UNAUTHORIZED = <<<EOT
{"message":"Unauthorized"}
EOT;

    const DECISIONS_STREAM_LIST = <<<EOT
{"new": [], "deleted": []}
EOT;
}
