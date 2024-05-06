# Release Instructions

Go to the ["manual release" GitHub Action](https://github.com/laravel/framework/actions/workflows/releases.yml). Then, choose "Run workflow", select the correct branch, and enter the version you wish to release. Next, press "Run workflow" to execute the action. The workflow will automatically update the version in `Application.php`, tag a new release, run the splitter script for the Illuminate components, generate release notes, create a GitHub Release, and update the `CHANGELOG.md` file.

<img width="400" alt="Screenshot 2024-05-06 at 10 46 04" src="https://github.com/laravel/framework/assets/594614/4dc5efc8-946e-4e96-9e79-8e26f92ea354">
