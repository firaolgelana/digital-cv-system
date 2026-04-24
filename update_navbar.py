import os
import re

directory = r'c:\xampp\htdocs\cv'

def process_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Skip files that don't have a header
    if '<header' not in content:
        return

    # Check if it has a top navbar
    if 'class="top-navbar"' not in content and 'class="topbar"' not in content:
        return

    # Extract the brand text
    title_match = re.search(r'<span class="text-gradient">(.*?)</span>', content)
    brand_title = title_match.group(1) if title_match else "DigiCV"

    # Extract the avatar initials
    avatar_match = re.search(r'<div class="avatar.*?">(.*?)</div>', content)
    avatar = avatar_match.group(1) if avatar_match else "US"

    # Extract the back button if it exists
    back_button = ""
    # Look for Back Home, Back to Resumes, Back Dashboard, etc
    back_match = re.search(r'<a href="([^"]+)".*?><i class="(fas fa-arrow-left[^"]*)"></i>(.*?)</a\s*>', content)
    if back_match:
        back_href = back_match.group(1)
        back_icon = back_match.group(2)
        back_text = back_match.group(3).strip()
        back_button = f'<a href="{back_href}"><i class="{back_icon}"></i> {back_text}</a>'
    else:
        # Provide a default back button using history back
        back_button = '<a href="javascript:history.back()"><i class="fas fa-arrow-left"></i> Back</a>'

    # The new standard header structure
    new_header = f"""    <header class="top-navbar">
      <div class="brand-area">
        <div class="brand-icon"><i class="fas fa-file-signature"></i></div>
        <span class="text-gradient">{brand_title}</span>
      </div>
      <button class="hamburger-btn" id="mobile-menu-toggle"><i class="fas fa-bars"></i></button>
      <nav class="nav-links" id="main-nav">
        {back_button}
        <a href="index.html"><i class="fas fa-home"></i> Home</a>
        <a href="profile.html" class="mobile-only"><i class="fas fa-user-cog"></i> Profile Settings</a>
        <a href="index.html" class="mobile-only" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Sign out</a>
      </nav>
      <div class="nav-user">
        <a href="profile.html" style="text-decoration:none"><div class="avatar avatar--sm avatar--primary" title="Profile Settings">{avatar}</div></a>
        <a class="btn btn-ghost btn-icon" href="index.html" title="Sign out"><i class="fas fa-sign-out-alt"></i></a>
      </div>
    </header>"""

    # Replace old header
    # Standardize classes first to make regex easier
    content = re.sub(r'<header class="topbar">', '<header class="top-navbar">', content)
    
    # Regex to find header ... /header. 
    # Use re.DOTALL to match across lines
    content = re.sub(r'\s*<header class="top-navbar">.*?</header>', '\n' + new_header, content, flags=re.DOTALL)

    # Add mobile script if it doesn't already exist
    mobile_script = """
    <script>
      // Mobile Menu Toggle
      if(document.getElementById('mobile-menu-toggle') && !window.mobileMenuInitialized){
        document.getElementById('mobile-menu-toggle').addEventListener('click', () => {
          document.getElementById('main-nav').classList.toggle('mobile-open');
        });
        window.mobileMenuInitialized = true;
      }
    </script>
  </body>"""

    if "mobile-menu-toggle" not in content.split("</header>")[-1]:
       content = content.replace("</body>", mobile_script)
    else:
       # If it has mobile-menu-toggle but no logic, add it
       if "mobileMenuInitialized" not in content and "classList.toggle('mobile-open')" not in content:
           content = content.replace("</body>", mobile_script)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Updated {filepath}")

for root, _, files in os.walk(directory):
    for file in files:
        if file.endswith('.html') or file.endswith('.php'):
            process_file(os.path.join(root, file))

print("All files updated successfully.")
