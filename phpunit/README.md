## phpunit

### create-jobs

#### 使用方法

```
php create-jobs.php -u /path/to/phpunit/tests -s http://localhost:8080/ -j /path/to/jenkins-cli.jar -i phpunit-start
```

#### オプション
<table>
  <tr>
    <td>u</td><td>**必須**テストケースファイルのディレクトリパス。*.phpでファイルを検索し全て追加します。既に存在するものは追加しません。</td>
  </tr>
  <tr>
    <td>s</td><td>**必須**jenkinsのURLをしています。ジョブの追加や更新で利用するjenkins-cliに必要です。</td>
  </tr>
  <tr>
    <td>j</td><td>**必須**同様にjenkins-cliが使用します。</td>
  </tr>
  <tr>
    <td>i</td><td>**必須**起点となるジョブを指定します。</td>
  </tr>
  <tr>
    <td>p</td><td>**必須**ジョブ名をプレフィクスを指定可能です。省略すると`phpunit`が採用されます。</td>
  </tr>
</table>


