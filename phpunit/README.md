## phpunit

### create-jobs

#### 使用方法

```
php create-jobs.php -u /path/to/phpunit/tests -s http://localhost:8080/ -j /path/to/jenkins-cli.jar -i phpunit-start
```

#### 必須オプション

##### u
テストケースファイルのディレクトリパス。*.phpでファイルを検索し全て追加します。既に存在するものは追加しません。

##### s
jenkinsのURLを指定します。ジョブの追加や更新で利用するjenkins-cliに必要です。

##### j
jenkins-cli.jarのパスを指定します。同様にjenkins-cliが使用します。

##### i
起点となるジョブを指定します。既に存在するジョブでなければなりません。ブラウザで作成して下さい。

#### オプション

##### p
ジョブ名をプレフィクスを指定可能です。省略すると`phpunit`が採用されます。
