# 🚀 WC Superadmin

**Centralized Technical Support Access for WooCommerce Webshops.**

Manage and access all your delivered WooCommerce sites from a single dashboard. No more manual user management across hundreds of sites. One userbase, one dashboard, instant access.

---

## 🛠 Features

- **Centralized Hub**: A single dashboard to manage and access all your client sites.
- **Magic Login Links**: Secure, one-time, time-limited login links for instant Administrator access.
- **Asymmetric RSA Security**: Industry-standard Public/Private key cryptography. Your Hub creates the links, and your Clients verify them—secure even if a client site is compromised.
- **Automatic Registration**: Client sites automatically report back to the Hub upon configuration.
- **Disposable Support Users**: Dynamically provisions support accounts with randomized passwords to maintain audit trails without creating backdoors.
- **Activity Logging**: Full audit logs on both the Hub (who generated a link) and the Client (who logged in and from where).
- **In-Dashboard Updates**: Integrated with the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) for seamless updates directly from GitHub.

---

## 📦 Components

The system consists of two WordPress plugins:

1.  **`wc-superadmin-hub`**: Install this on your central management site. It holds the "Master" Private Key and the dashboard.
2.  **`wc-superadmin-client`**: Install this on every WooCommerce webshop you deliver. It holds the Public Key to verify support logins.

---

## 🚀 Getting Started

### 1. Set up the Hub
1.  Install and activate the `wc-superadmin-hub.zip` plugin on your central site.
2.  Navigate to **Superadmin Hub** in the WordPress menu.
3.  Copy the generated **RSA Public Key**.

### 2. Set up the Client(s)
1.  Install and activate the `wc-superadmin-client.zip` plugin on your client's WooCommerce site.
2.  Go to **Settings → Superadmin Client**.
3.  Paste the **RSA Public Key** from your Hub.
4.  Ensure the **Central Hub URL** is correct (defaults to `https://admindemo.kimbr.no`).
5.  Click **Save & Register with Hub**.

### 3. Usage
1.  Back on your Hub dashboard, you will see the client site listed automatically.
2.  Click **Generate Login Link** for any site.
3.  Follow the generated link to be instantly logged in as an Administrator on that site!

---

## 💻 Development & Deployment

The project includes a PowerShell build script to handle versioning and deployment:

```powershell
# Standard release (bumps patch version)
.\build.ps1

# Minor release with custom message
.\build.ps1 -BumpType minor -Message "Added new logging feature"
```

The script automatically:
- Bumps version numbers in PHP and JSON metadata.
- Rebuilds the plugin `.zip` files.
- Commits and pushes all changes to GitHub.

---

## 🛡 Security

We use **Asymmetric Cryptography (RSA-2048)**.
- The **Private Key** never leaves your Hub. It is the only thing that can sign a valid login token.
- The **Public Key** is shared with clients. It can only *verify* signatures.
- **Audience Protection**: Tokens generated for Site A will be rejected by Site B, preventing "token replay" attacks across your network.
- **Expiration**: Tokens are strictly valid for only 60 seconds from the moment they are generated.

---

## 📜 License

GPLv2 or later. Developed by KimB.
