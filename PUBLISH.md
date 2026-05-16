# 把 `omnivideo/omnivideo-sdk` 发布到 Packagist

Packagist 自己不存代码，它只是个索引 —— 真正的源码在公开 Git 仓库里，打了 tag 之后 Packagist 通过 webhook 同步。

## 前置条件

1. 一个公开 Git 仓库（推荐 GitHub）来放 PHP SDK 源码。
   - 仓库名建议：`omnivideo-sdk-php`
   - 仓库 owner 建议：`omnivideo`（和 composer.json 里 `"name": "omnivideo/omnivideo-sdk"` 的 vendor 前缀一致）
   - GitHub Personal Access Token，scope 勾 `public_repo`（在 <https://github.com/settings/tokens/new> 生成）。
2. 在 <https://packagist.org/register/> 注册 Packagist 账号，并完成邮箱验证。
3. 在 <https://packagist.org/profile/> 拿到 **API token**（页面上有「Show API Token」按钮）。

## 发布步骤

```bash
cd php
git init
git add .
git commit -m "Initial release 0.1.0"
git branch -M main
git remote add origin https://<GITHUB_TOKEN>@github.com/omnivideo/omnivideo-sdk-php.git
git push -u origin main
git tag v0.1.0
git push origin v0.1.0
```

然后在 Packagist 注册这个包（只需做一次）：

**方式 A：网页（最稳）**

1. 打开 <https://packagist.org/packages/submit>
2. 粘贴仓库 URL：`https://github.com/omnivideo/omnivideo-sdk-php`
3. 点 Submit，Packagist 会读取 `composer.json` 并把包注册为 `omnivideo/omnivideo-sdk`。

**方式 B：API（适合自动化）**

```bash
curl -X POST "https://packagist.org/api/create-package?username=<你的Packagist用户名>&apiToken=<APIToken>" \
  -H "Content-Type: application/json" \
  -d '{"repository":{"url":"https://github.com/omnivideo/omnivideo-sdk-php"}}'
```

注册成功后，到包页面（`https://packagist.org/packages/omnivideo/omnivideo-sdk`）的 Settings 里把 GitHub Service Hook 打开，**之后再打新 tag 会自动同步**。

## 后续版本升级

```bash
git tag v0.2.0
git push origin v0.2.0
```

Packagist 几秒内就会显示新版本。可以手动到包页面点「Update」按钮强制刷新。
