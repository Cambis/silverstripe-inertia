/** @type {import('lint-staged').Config} */
module.exports = {
  '*.php': [
    'php vendor/bin/parallel-lint src tests --colors --blame',
    'php vendor/bin/ecs check --fix',
    'php vendor/bin/phpstan analyse --ansi --memory-limit=-1',
  ],
  'composer.json': [
    'composer normalize --ansi'
  ],
};
