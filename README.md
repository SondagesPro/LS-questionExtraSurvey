# questionExtraSurvey

Add survey inside survey : create question with list of response in another survey

**Warning** : This plugin is not compatible with theme option ajax mode. You must use a theme without ajax mode or deactivate the ajax mode in theme option.

## Installation

This plugin is tested with LimeSurvey 2.73, and 3.16.1

### Via GIT
- Go to your LimeSurvey Directory
- Clone in plugins/questionExtraSurvey directory `git clone https://gitlab.com/SondagesPro/QuestionSettingsType/questionExtraSurvey.git questionExtraSurvey`

### Via ZIP dowload
- Download <https://dl.sondages.pro/questionExtraSurvey.zip>
- Extract : `unzip questionExtraSurvey.zip`
- Move the directory to  plugins/ directory inside LimeSUrvey

## Usage

### Basic usage with token enables survey's

1. Create the 1st survey and add a long text question
    - The plugin is done for survey with token, not anonymous, token-based response persistence and allow edit response activated.
2. Create the second survey with
    - An hidden question to get the response identification of the first survey (for example surveyLinkSrid)
    - Your real question
3. Create and set the token to the 2 surveys with same token list. The second survey must be
    - **Not anonymous**
    - Token-based response **persistence not activated** or **Use left up to 1** (if you want to limit number of response by token)
    - **Allow multiple responses** activated
4. Update the short text question settings with
    - Survey to use: the survey id of the second survey
    - Question for response id : the response code for identification (for example surveyLinkSrid)
    - You can use a question for the list : it can be text, single choice, numerci, date or equation question type.

This plugin is compatible with [responseListAndManage](https://gitlab.com/SondagesPro/managament/responseListAndManage) user group.

### Improve relations between related survey using any questions field for relation

When export, import first survey, or if you need to deactivate the first survey. When you reload previous response table : the link between reponse are totally lost.

You can use a [generateUniqId](https://gitlab.com/SondagesPro/QuestionSettingsType/generateUniqId) question for the link between surveys.

1. Create you uniqId question in the first survey (title : uniqId here)
2. Set _Other question fields for relation_ to surveyLinkSrid:{uniqId.NAOK}

You can use any question, for example : you can use TOKEN attribute for group.

If you don't add uniqId or use token : anybody can add new response using prefill value form url.

## Home page & Copyright
- HomePage <https://extensions.sondages.pro/>
- Code repository <https://gitlab.com/SondagesPro/QuestionSettingsType/questionExtraSurvey>
- Copyright © 2017-2019 Denis Chenu <www.sondages.pro>
- Copyright © 2017 OECD (Organisation for Economic Co-operation and Development ) <www.oecd.org>
- [![Donate](https://liberapay.com/assets/widgets/donate.svg)](https://liberapay.com/SondagesPro/) : [Donate on Liberapay](https://liberapay.com/SondagesPro/)

Distributed under [GNU GENERAL PUBLIC LICENSE Version 3](https://gnu.org/licenses/gpl-3.0.txt) licence
