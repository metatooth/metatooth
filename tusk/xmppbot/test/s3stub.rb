require 'aws-sdk-s3'
Aws.config.update(s3: { endpoint: 'http://localhost:8099', force_path_style: true })
