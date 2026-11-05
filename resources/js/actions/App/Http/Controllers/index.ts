import Auth from './Auth'
import WorkspaceController from './WorkspaceController'
import ProjectController from './ProjectController'
import TaskController from './TaskController'

const Controllers = {
    Auth: Object.assign(Auth, Auth),
    WorkspaceController: Object.assign(WorkspaceController, WorkspaceController),
    ProjectController: Object.assign(ProjectController, ProjectController),
    TaskController: Object.assign(TaskController, TaskController),
}

export default Controllers